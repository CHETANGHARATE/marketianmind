<?php

namespace App\Http\Controllers\Student;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Services\CouponService;
use App\Services\PricingService;
use App\Services\RazorpayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Initiate a course purchase and redirect to the checkout screen.
     */
    public function purchase(Request $request, Course $course, RazorpayService $razorpayService, PricingService $pricingService): RedirectResponse
    {
        $user = $request->user();

        if (! $course->isPublished()) {
            abort(404, 'Course not found or unavailable.');
        }

        if ($course->is_free) {
            return redirect()
                ->route('student.courses.enroll', $course)
                ->with('status', 'This is a free course. You can enroll immediately!');
        }

        $purchaseType = $user->getCoursePurchaseType($course);

        if ($purchaseType === \App\Enums\CoursePurchaseType::NOT_ELIGIBLE) {
            return redirect()
                ->route('courses.show', $course)
                ->with('error', 'Your enrollment in this course has been cancelled. Please contact support.');
        }

        // Authoritative server-side pricing resolution
        $pricing = $pricingService->resolveForProduct($course, $user);
        $baseAmountInPaise = $pricing['base_price_in_paise'];
        $payableAmountInPaise = $pricing['offer_price_in_paise'];
        $offerDiscountInPaise = $pricing['offer_discount_in_paise'];
        $offerId = $pricing['offer']?->id;

        // Check for recent pending order that can be reused
        $order = Order::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', OrderStatus::PENDING->value)
            ->where('created_at', '>=', now()->subHours(24))
            ->latest()
            ->first();

        if (! $order) {
            $orderNumber = 'MM-ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $order = Order::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'bundle_id' => null,
                'offer_id' => $offerId,
                'offer_discount_amount' => $offerDiscountInPaise,
                'order_number' => $orderNumber,
                'original_amount' => $baseAmountInPaise,
                'discount_amount' => 0,
                'amount' => $payableAmountInPaise,
                'currency' => 'INR',
                'status' => OrderStatus::PENDING,
                'metadata' => [
                    'pricing_snapshot' => $pricing,
                    'purchase_type' => $purchaseType->value,
                ],
            ]);
        } else {
            // Update reused order if it has no coupon applied
            if (! $order->hasCoupon()) {
                $order->update([
                    'original_amount' => $baseAmountInPaise,
                    'offer_id' => $offerId,
                    'offer_discount_amount' => $offerDiscountInPaise,
                    'amount' => $payableAmountInPaise,
                    'metadata' => array_merge($order->metadata ?? [], [
                        'pricing_snapshot' => $pricing,
                        'purchase_type' => $purchaseType->value,
                    ]),
                ]);
            }
        }

        // If Razorpay order ID is missing or new, and amount > 0, create Razorpay order
        if (! $order->razorpay_order_id && $order->amount > 0) {
            $razorpayOrder = $razorpayService->createOrder(
                $order->amount,
                $order->order_number,
                [
                    'course_id' => (string) $course->id,
                    'user_id' => (string) $user->id,
                    'order_id' => (string) $order->id,
                ]
            );

            $order->update([
                'razorpay_order_id' => $razorpayOrder['id'],
            ]);
        }

        return redirect()->route('student.courses.checkout', $order);
    }

    /**
     * Initiate a course bundle purchase and redirect to checkout screen.
     */
    public function purchaseBundle(Request $request, Bundle $bundle, RazorpayService $razorpayService, PricingService $pricingService): RedirectResponse
    {
        $user = $request->user();

        if (! $bundle->isPublished()) {
            abort(404, 'Bundle not found or unavailable.');
        }

        // Check if student already owns ALL courses in this bundle
        if ($bundle->hasUserAccess($user)) {
            return redirect()
                ->route('bundles.show', $bundle)
                ->with('status', 'You already have active access to all courses included in this bundle.');
        }

        // Authoritative server-side pricing resolution
        $pricing = $pricingService->resolveForProduct($bundle, $user);
        $baseAmountInPaise = $pricing['base_price_in_paise'];
        $payableAmountInPaise = $pricing['offer_price_in_paise'];
        $offerDiscountInPaise = $pricing['offer_discount_in_paise'];
        $offerId = $pricing['offer']?->id;

        // Check for recent pending order that can be reused
        $order = Order::query()
            ->where('user_id', $user->id)
            ->where('bundle_id', $bundle->id)
            ->where('status', OrderStatus::PENDING->value)
            ->where('created_at', '>=', now()->subHours(24))
            ->latest()
            ->first();

        if (! $order) {
            $orderNumber = 'MM-BND-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $order = Order::create([
                'user_id' => $user->id,
                'bundle_id' => $bundle->id,
                'course_id' => null,
                'offer_id' => $offerId,
                'offer_discount_amount' => $offerDiscountInPaise,
                'order_number' => $orderNumber,
                'original_amount' => $baseAmountInPaise,
                'discount_amount' => 0,
                'amount' => $payableAmountInPaise,
                'currency' => 'INR',
                'status' => OrderStatus::PENDING,
                'metadata' => ['pricing_snapshot' => $pricing],
            ]);
        } else {
            if (! $order->hasCoupon()) {
                $order->update([
                    'original_amount' => $baseAmountInPaise,
                    'offer_id' => $offerId,
                    'offer_discount_amount' => $offerDiscountInPaise,
                    'amount' => $payableAmountInPaise,
                    'metadata' => array_merge($order->metadata ?? [], ['pricing_snapshot' => $pricing]),
                ]);
            }
        }

        // Create Razorpay order if needed
        if (! $order->razorpay_order_id && $order->amount > 0) {
            $razorpayOrder = $razorpayService->createOrder(
                $order->amount,
                $order->order_number,
                [
                    'bundle_id' => (string) $bundle->id,
                    'user_id' => (string) $user->id,
                    'order_id' => (string) $order->id,
                ]
            );

            $order->update([
                'razorpay_order_id' => $razorpayOrder['id'],
            ]);
        }

        return redirect()->route('student.courses.checkout', $order);
    }

    /**
     * Display the secure Razorpay Checkout screen for an order.
     */
    public function showCheckout(Request $request, Order $order, RazorpayService $razorpayService): View|RedirectResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this checkout session.');
        }

        if ($order->isPaid()) {
            if ($order->isBundleOrder() && $order->bundle) {
                return redirect()
                    ->route('bundles.show', $order->bundle)
                    ->with('status', 'This order has already been paid.');
            }

            return redirect()
                ->route('student.courses.show', $order->course)
                ->with('status', 'This order has already been paid.');
        }

        $course = $order->course;
        $bundle = $order->bundle;
        $keyId = $razorpayService->getKeyId() ?? config('services.razorpay.key', 'rzp_test_mock');

        app(\App\Services\ConversionTrackingService::class)->track(
            \App\Enums\ConversionEventName::CHECKOUT_STARTED,
            [
                'course_id' => $order->course_id,
                'bundle_id' => $order->bundle_id,
                'metadata' => [
                    'order_id' => $order->id,
                    'amount' => $order->amount,
                ],
            ]
        );

        if ($order->isRenewal()) {
            app(\App\Services\ConversionTrackingService::class)->track(
                \App\Enums\ConversionEventName::RENEWAL_CHECKOUT_STARTED,
                [
                    'course_id' => $order->course_id,
                    'user_id' => $request->user()->id,
                    'metadata' => [
                        'order_id' => $order->id,
                        'amount' => $order->amount,
                    ],
                ]
            );

            if ($course) {
                app(\App\Services\RenewalAnalyticsService::class)->logCrmRenewalActivity(
                    $request->user(),
                    $course,
                    'renewal_checkout_started',
                    "Student started renewal checkout for course: {$course->title}",
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'amount' => round(((int) $order->amount) / 100, 2),
                    ]
                );
            }
        }

        return view('student.checkout', compact('order', 'course', 'bundle', 'keyId'));
    }

    /**
     * Apply a promotional coupon to the order.
     */
    public function applyCoupon(
        Request $request,
        Order $order,
        CouponService $couponService,
        RazorpayService $razorpayService
    ): RedirectResponse {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this checkout session.');
        }

        if ($order->isPaid()) {
            return redirect()->route('student.courses.show', $order->course)
                ->with('status', 'This order is already paid.');
        }

        $validated = $request->validate([
            'coupon_code' => ['required', 'string', 'max:50'],
        ]);

        $result = $couponService->applyToOrder(
            $order,
            $validated['coupon_code'],
            $request->user(),
            $razorpayService
        );

        if (! $result['success']) {
            return redirect()->route('student.courses.checkout', $order)
                ->with('error', $result['message']);
        }

        return redirect()->route('student.courses.checkout', $order)
            ->with('status', $result['message']);
    }

    /**
     * Remove applied coupon from the order.
     */
    public function removeCoupon(
        Request $request,
        Order $order,
        CouponService $couponService,
        RazorpayService $razorpayService
    ): RedirectResponse {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this checkout session.');
        }

        if ($order->isPaid()) {
            return redirect()->route('student.courses.show', $order->course)
                ->with('status', 'This order is already paid.');
        }

        $result = $couponService->removeFromOrder($order, $razorpayService);

        if (! $result['success']) {
            return redirect()->route('student.courses.checkout', $order)
                ->with('error', $result['message']);
        }

        return redirect()->route('student.courses.checkout', $order)
            ->with('status', $result['message']);
    }

    /**
     * Complete enrollment for a 100% discount free order.
     */
    public function completeFree(Request $request, Order $order, CouponService $couponService): RedirectResponse
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this checkout session.');
        }

        if ($order->isPaid()) {
            if ($order->isBundleOrder() && $order->bundle) {
                return redirect()->route('bundles.show', $order->bundle)
                    ->with('status', 'This order is already paid.');
            }

            return redirect()->route('student.courses.show', $order->course)
                ->with('status', 'This order is already paid.');
        }

        if ($order->amount > 0) {
            return redirect()->route('student.courses.checkout', $order)
                ->with('error', 'This order requires payment and cannot be completed for free.');
        }

        DB::transaction(function () use ($order, $user, $couponService) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            $lockedOrder->markPaid();

            // Record coupon usage
            $couponService->recordUsage($lockedOrder);

            // Record offer usage if applicable
            if ($lockedOrder->offer_id) {
                \App\Models\Offer::where('id', $lockedOrder->offer_id)->increment('times_used');
            }

            // Authoritative Order Fulfillment
            app(\App\Services\OrderFulfillmentService::class)->fulfillOrder($lockedOrder);

            $user->notify(new \App\Notifications\PaymentSuccessNotification($lockedOrder));
            app(\App\Services\TransactionalMailService::class)->sendOrderConfirmation($lockedOrder);
        });

        return redirect()->route('payment.success', $order)
            ->with('status', 'Congratulations! Your course access is activated for free.');
    }
}