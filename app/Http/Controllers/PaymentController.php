<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CouponService;
use App\Services\RazorpayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Verify payment signature and grant course access upon success.
     */
    public function verify(Request $request, RazorpayService $razorpayService): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $order = Order::query()
            ->where('razorpay_order_id', $validated['razorpay_order_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $order) {
            abort(404, 'Matching order could not be located.');
        }

        $isValidSignature = $razorpayService->verifyPaymentSignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature']
        );

        if (! $isValidSignature) {
            Log::warning('Payment verification failed for order', [
                'order_id' => $order->id,
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
            ]);

            Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'course_id' => $order->course_id,
                'bundle_id' => $order->bundle_id,
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_order_id' => $validated['razorpay_order_id'],
                'amount' => $order->amount,
                'currency' => $order->currency,
                'status' => PaymentStatus::FAILED,
                'failure_description' => 'Cryptographic signature verification failed.',
            ]);

            $order->markFailed();

            app(\App\Services\ConversionTrackingService::class)->track(
                \App\Enums\ConversionEventName::PAYMENT_FAILED,
                [
                    'course_id' => $order->course_id,
                    'bundle_id' => $order->bundle_id,
                    'user_id' => $user->id,
                    'metadata' => [
                        'order_id' => $order->id,
                        'amount' => $order->amount,
                        'reason' => 'Cryptographic signature verification failed',
                    ],
                ]
            );

            return redirect()
                ->route('payment.failed', $order)
                ->with('error', 'Payment verification could not be validated. Please try again or contact support.');
        }

        // Idempotent execution in locked database transaction
        DB::transaction(function () use ($order, $user, $validated) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            Payment::updateOrCreate(
                [
                    'razorpay_payment_id' => $validated['razorpay_payment_id'],
                ],
                [
                    'order_id' => $lockedOrder->id,
                    'user_id' => $user->id,
                    'course_id' => $lockedOrder->course_id,
                    'bundle_id' => $lockedOrder->bundle_id,
                    'razorpay_order_id' => $validated['razorpay_order_id'],
                    'amount' => $lockedOrder->amount,
                    'currency' => $lockedOrder->currency,
                    'status' => PaymentStatus::CAPTURED,
                    'captured' => true,
                    'paid_at' => now(),
                ]
            );

            $wasPaid = $lockedOrder->isPaid();

            if (! $wasPaid) {
                $lockedOrder->markPaid();

                // Increment offer usage if applicable
                if ($lockedOrder->offer_id) {
                    \App\Models\Offer::where('id', $lockedOrder->offer_id)->increment('times_used');
                }
            }

            // Record coupon usage if order used a coupon
            app(CouponService::class)->recordUsage($lockedOrder);

            // Attribute referral conversion if order belongs to a referred student
            app(\App\Services\ReferralService::class)->attributeConversion($lockedOrder);

            // Auto-convert matching CRM leads
            app(\App\Services\LeadService::class)->autoConvertMatchingLeads($user, 'course purchase');

            // Create or activate enrollment(s)
            if ($lockedOrder->isBundleOrder() && $lockedOrder->bundle) {
                $bundleCourses = $lockedOrder->bundle->publishedCourses;
                foreach ($bundleCourses as $bCourse) {
                    $enrollment = Enrollment::query()
                        ->where('user_id', $user->id)
                        ->where('course_id', $bCourse->id)
                        ->first();

                    if (! $enrollment) {
                        Enrollment::create([
                            'user_id' => $user->id,
                            'course_id' => $bCourse->id,
                            'status' => EnrollmentStatus::ACTIVE,
                            'enrolled_at' => now(),
                        ]);
                        app(\App\Services\EngagementService::class)->handleEnrollment($user, $bCourse, true);
                    } elseif (! $enrollment->isActive() && ! $enrollment->isCompleted()) {
                        $enrollment->update([
                            'status' => EnrollmentStatus::ACTIVE,
                            'enrolled_at' => now(),
                        ]);
                    }
                }

                app(\App\Services\MarketingAutomationService::class)->dispatchTrigger(
                    \App\Enums\AutomationTrigger::BUNDLE_PURCHASED,
                    $user,
                    ['bundle_id' => $lockedOrder->bundle_id],
                    'order_bundle_' . $lockedOrder->id
                );
            } elseif ($lockedOrder->course_id) {
                $enrollment = Enrollment::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $lockedOrder->course_id)
                    ->first();

                if (! $enrollment) {
                    Enrollment::create([
                        'user_id' => $user->id,
                        'course_id' => $lockedOrder->course_id,
                        'status' => EnrollmentStatus::ACTIVE,
                        'enrolled_at' => now(),
                    ]);
                } elseif (! $enrollment->isActive() && ! $enrollment->isCompleted()) {
                    $enrollment->update([
                        'status' => EnrollmentStatus::ACTIVE,
                        'enrolled_at' => now(),
                    ]);
                }

                if ($lockedOrder->course) {
                    app(\App\Services\EngagementService::class)->handleEnrollment($user, $lockedOrder->course, true);
                }

                app(\App\Services\MarketingAutomationService::class)->dispatchTrigger(
                    \App\Enums\AutomationTrigger::COURSE_ENROLLED,
                    $user,
                    ['course_id' => $lockedOrder->course_id],
                    'order_course_' . $lockedOrder->id
                );
            }

            $user->notify(new \App\Notifications\PaymentSuccessNotification($lockedOrder));
            app(\App\Services\TransactionalMailService::class)->sendOrderConfirmation($lockedOrder);

            app(\App\Services\ConversionTrackingService::class)->track(
                \App\Enums\ConversionEventName::PAYMENT_SUCCESS,
                [
                    'course_id' => $lockedOrder->course_id,
                    'bundle_id' => $lockedOrder->bundle_id,
                    'user_id' => $user->id,
                    'metadata' => [
                        'order_id' => $lockedOrder->id,
                        'amount' => $lockedOrder->amount,
                        'currency' => $lockedOrder->currency,
                    ],
                ]
            );

            if ($lockedOrder->isBundleOrder()) {
                app(\App\Services\ConversionTrackingService::class)->track(
                    \App\Enums\ConversionEventName::BUNDLE_PURCHASED,
                    [
                        'bundle_id' => $lockedOrder->bundle_id,
                        'user_id' => $user->id,
                        'metadata' => [
                            'order_id' => $lockedOrder->id,
                            'amount' => $lockedOrder->amount,
                        ],
                    ]
                );
            } elseif ($lockedOrder->course_id) {
                app(\App\Services\ConversionTrackingService::class)->track(
                    \App\Enums\ConversionEventName::COURSE_ENROLLED,
                    [
                        'course_id' => $lockedOrder->course_id,
                        'user_id' => $user->id,
                        'metadata' => [
                            'order_id' => $lockedOrder->id,
                            'amount' => $lockedOrder->amount,
                        ],
                    ]
                );
            }
        });

        return redirect()->route('payment.success', $order);
    }

    /**
     * Show payment success page.
     */
    public function success(Request $request, Order $order): View
    {
        if ($order->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $order->load(['course', 'bundle', 'payments']);
        $payment = $order->payments()->where('status', PaymentStatus::CAPTURED->value)->latest()->first();

        return view('payment.success', compact('order', 'payment'));
    }

    /**
     * Show payment failed page.
     */
    public function failed(Request $request, Order $order): View
    {
        if ($order->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $order->load(['course', 'bundle']);

        return view('payment.failed', compact('order'));
    }
}