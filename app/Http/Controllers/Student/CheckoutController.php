<?php

namespace App\Http\Controllers\Student;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Services\CouponService;
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
    public function purchase(Request $request, Course $course, RazorpayService $razorpayService): RedirectResponse
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

        if ($user->isEnrolledIn($course)) {
            return redirect()
                ->route('student.courses.show', $course)
                ->with('status', 'You are already enrolled in this course.');
        }

        $amountInPaise = $course->effectivePriceInPaise();

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
                'order_number' => $orderNumber,
                'original_amount' => $amountInPaise,
                'discount_amount' => 0,
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'status' => OrderStatus::PENDING,
            ]);
        } else {
            // Ensure original_amount is set on reused order
            if (! $order->original_amount) {
                $order->update(['original_amount' => $amountInPaise]);
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
     * Display the secure Razorpay Checkout screen for an order.
     */
    public function showCheckout(Request $request, Order $order, RazorpayService $razorpayService): View|RedirectResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this checkout session.');
        }

        if ($order->isPaid()) {
            return redirect()
                ->route('student.courses.show', $order->course)
                ->with('status', 'This order has already been paid.');
        }

        $course = $order->course;
        $keyId = $razorpayService->getKeyId() ?? config('services.razorpay.key', 'rzp_test_mock');

        return view('student.checkout', compact('order', 'course', 'keyId'));
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

            // Grant active course enrollment
            Enrollment::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $lockedOrder->course_id,
                ],
                [
                    'status' => EnrollmentStatus::ACTIVE,
                    'enrolled_at' => now(),
                ]
            );

            $user->notify(new \App\Notifications\PaymentSuccessNotification($lockedOrder));
            if ($lockedOrder->course) {
                $user->notify(new \App\Notifications\CourseEnrollmentNotification($lockedOrder->course));
            }
            app(\App\Services\TransactionalMailService::class)->sendOrderConfirmation($lockedOrder);
        });

        return redirect()->route('payment.success', $order)
            ->with('status', 'Congratulations! Your course access is activated for free.');
    }
}