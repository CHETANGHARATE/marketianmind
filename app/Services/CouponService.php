<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Validate a coupon against a user, course, and order amount.
     *
     * @return array{valid: bool, coupon: ?Coupon, discount_amount: int, final_amount: int, message: ?string}
     */
    public function validate(string|Coupon $couponInput, User $user, Course $course, int $amountInPaise): array
    {
        $coupon = is_string($couponInput)
            ? Coupon::where('code', strtoupper(trim($couponInput)))->first()
            : $couponInput;

        if (! $coupon) {
            return [
                'valid' => false,
                'coupon' => null,
                'discount_amount' => 0,
                'final_amount' => $amountInPaise,
                'message' => 'The coupon code provided is invalid.',
            ];
        }

        if (! $coupon->is_active) {
            return [
                'valid' => false,
                'coupon' => $coupon,
                'discount_amount' => 0,
                'final_amount' => $amountInPaise,
                'message' => 'This coupon is currently inactive.',
            ];
        }

        if ($coupon->isUpcoming()) {
            return [
                'valid' => false,
                'coupon' => $coupon,
                'discount_amount' => 0,
                'final_amount' => $amountInPaise,
                'message' => 'This coupon is not active yet. It will be valid from ' . $coupon->starts_at->format('M d, Y') . '.',
            ];
        }

        if ($coupon->isExpired()) {
            return [
                'valid' => false,
                'coupon' => $coupon,
                'discount_amount' => 0,
                'final_amount' => $amountInPaise,
                'message' => 'This coupon has expired on ' . $coupon->expires_at->format('M d, Y') . '.',
            ];
        }

        if ($coupon->isCourseSpecific() && $coupon->course_id !== $course->id) {
            return [
                'valid' => false,
                'coupon' => $coupon,
                'discount_amount' => 0,
                'final_amount' => $amountInPaise,
                'message' => 'This coupon is not applicable to the selected course.',
            ];
        }

        if ($coupon->min_order_amount && $amountInPaise < $coupon->min_order_amount) {
            $minFormatted = '₹' . number_format($coupon->min_order_amount / 100, 2);
            return [
                'valid' => false,
                'coupon' => $coupon,
                'discount_amount' => 0,
                'final_amount' => $amountInPaise,
                'message' => "This coupon requires a minimum order value of {$minFormatted}.",
            ];
        }

        if ($coupon->hasReachedGlobalLimit()) {
            return [
                'valid' => false,
                'coupon' => $coupon,
                'discount_amount' => 0,
                'final_amount' => $amountInPaise,
                'message' => 'This coupon has reached its maximum total redemptions.',
            ];
        }

        if ($coupon->hasUserReachedLimit($user)) {
            return [
                'valid' => false,
                'coupon' => $coupon,
                'discount_amount' => 0,
                'final_amount' => $amountInPaise,
                'message' => 'You have already reached the maximum redemption limit for this coupon.',
            ];
        }

        $discount = $coupon->calculateDiscount($amountInPaise);
        $final = max(0, $amountInPaise - $discount);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount_amount' => $discount,
            'final_amount' => $final,
            'message' => null,
        ];
    }

    /**
     * Apply coupon to a pending order and recalculate totals.
     *
     * @return array{success: bool, order?: Order, discount?: int, message: string}
     */
    public function applyToOrder(Order $order, string $couponCode, User $user, RazorpayService $razorpayService): array
    {
        $baseAmount = $order->original_amount ?? $order->amount;
        $course = $order->course;

        $validation = $this->validate($couponCode, $user, $course, $baseAmount);

        if (! $validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'] ?? 'Coupon could not be applied.',
            ];
        }

        /** @var Coupon $coupon */
        $coupon = $validation['coupon'];
        $discountAmount = $validation['discount_amount'];
        $finalAmount = $validation['final_amount'];

        $order->original_amount = $baseAmount;
        $order->coupon_id = $coupon->id;
        $order->coupon_code = $coupon->code;
        $order->discount_amount = $discountAmount;
        $order->amount = $finalAmount;

        if ($finalAmount > 0) {
            $razorpayOrder = $razorpayService->createOrder(
                $finalAmount,
                $order->order_number,
                [
                    'course_id' => (string) $course->id,
                    'user_id' => (string) $user->id,
                    'order_id' => (string) $order->id,
                    'coupon_code' => $coupon->code,
                ]
            );

            $order->razorpay_order_id = $razorpayOrder['id'];
        } else {
            // Free 100% coupon - no Razorpay order required
            $order->razorpay_order_id = null;
        }

        $order->save();

        $discountDisplay = '₹' . number_format($discountAmount / 100, 2);

        return [
            'success' => true,
            'order' => $order,
            'discount' => $discountAmount,
            'message' => "Coupon '{$coupon->code}' applied successfully! You saved {$discountDisplay}.",
        ];
    }

    /**
     * Remove applied coupon from an order.
     *
     * @return array{success: bool, message: string}
     */
    public function removeFromOrder(Order $order, RazorpayService $razorpayService): array
    {
        if (! $order->hasCoupon()) {
            return [
                'success' => false,
                'message' => 'No coupon is currently applied to this order.',
            ];
        }

        $baseAmount = $order->original_amount ?? $order->amount;
        $course = $order->course;

        $order->amount = $baseAmount;
        $order->discount_amount = 0;
        $order->coupon_id = null;
        $order->coupon_code = null;

        $razorpayOrder = $razorpayService->createOrder(
            $baseAmount,
            $order->order_number,
            [
                'course_id' => (string) $course->id,
                'user_id' => (string) $order->user_id,
                'order_id' => (string) $order->id,
            ]
        );

        $order->razorpay_order_id = $razorpayOrder['id'];
        $order->save();

        return [
            'success' => true,
            'message' => 'Coupon has been removed.',
        ];
    }

    /**
     * Record coupon usage upon successful payment or free completion.
     */
    public function recordUsage(Order $order): ?CouponUsage
    {
        if (! $order->coupon_id) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            $usage = CouponUsage::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'coupon_id' => $order->coupon_id,
                    'user_id' => $order->user_id,
                    'discount_amount' => $order->discount_amount,
                ]
            );

            if ($usage->wasRecentlyCreated) {
                Coupon::where('id', $order->coupon_id)->increment('times_used');
            }

            return $usage;
        });
    }
}