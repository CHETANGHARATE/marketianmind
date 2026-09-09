<?php

namespace App\Http\Controllers\Student;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Services\RazorpayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->where('amount', $amountInPaise)
            ->where('created_at', '>=', now()->subHours(24))
            ->latest()
            ->first();

        if (! $order) {
            $orderNumber = 'MM-ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $order = Order::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'order_number' => $orderNumber,
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'status' => OrderStatus::PENDING,
            ]);
        }

        // If Razorpay order ID is missing or new, create Razorpay order
        if (! $order->razorpay_order_id) {
            $razorpayOrder = $razorpayService->createOrder(
                $amountInPaise,
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
}