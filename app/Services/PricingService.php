<?php

namespace App\Services;

use App\Models\Bundle;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Offer;
use App\Models\User;

class PricingService
{
    /**
     * Resolve authoritative server-side pricing for any purchasable product (Course or Bundle).
     *
     * @return array{
     *     product: Course|Bundle,
     *     product_type: string,
     *     product_id: int,
     *     base_price: float,
     *     base_price_in_paise: int,
     *     has_offer: bool,
     *     offer: ?Offer,
     *     offer_discount: float,
     *     offer_discount_in_paise: int,
     *     offer_price: float,
     *     offer_price_in_paise: int,
     *     has_coupon: bool,
     *     coupon: ?Coupon,
     *     coupon_discount: float,
     *     coupon_discount_in_paise: int,
     *     coupon_error: ?string,
     *     final_price: float,
     *     final_price_in_paise: int,
     *     total_savings: float,
     *     total_savings_in_paise: int,
     *     savings_percentage: int,
     *     formatted_base_price: string,
     *     formatted_final_price: string,
     *     formatted_savings: string
     * }
     */
    public function resolveForProduct(Course|Bundle $product, ?User $user = null, ?string $couponCode = null): array
    {
        $isCourse = $product instanceof Course;
        $productType = $isCourse ? 'course' : 'bundle';

        // 1. Calculate Base Price
        if ($isCourse && $product->is_free) {
            $basePriceInPaise = 0;
        } elseif ($isCourse) {
            $basePriceInPaise = $product->effectivePriceInPaise();
        } else {
            /** @var Bundle $product */
            $basePriceInPaise = $product->priceInPaise();
        }

        $basePriceInRupees = round($basePriceInPaise / 100, 2);

        // Free products do not receive offer or coupon discounts
        if ($basePriceInPaise <= 0) {
            return [
                'product' => $product,
                'product_type' => $productType,
                'product_id' => $product->id,
                'base_price' => 0.00,
                'base_price_in_paise' => 0,
                'has_offer' => false,
                'offer' => null,
                'offer_discount' => 0.00,
                'offer_discount_in_paise' => 0,
                'offer_price' => 0.00,
                'offer_price_in_paise' => 0,
                'has_coupon' => false,
                'coupon' => null,
                'coupon_discount' => 0.00,
                'coupon_discount_in_paise' => 0,
                'coupon_error' => null,
                'final_price' => 0.00,
                'final_price_in_paise' => 0,
                'total_savings' => 0.00,
                'total_savings_in_paise' => 0,
                'savings_percentage' => 0,
                'formatted_base_price' => 'Free',
                'formatted_final_price' => 'Free',
                'formatted_savings' => '₹0.00',
            ];
        }

        // 2. Resolve Active Promotional Offer
        $winningOffer = $this->getWinningOffer($product);
        $hasOffer = $winningOffer !== null;

        if ($hasOffer) {
            $offerDiscountInPaise = $winningOffer->calculateDiscount($basePriceInPaise);
            $offerPriceInPaise = max(0, $basePriceInPaise - $offerDiscountInPaise);
        } else {
            $offerDiscountInPaise = 0;
            $offerPriceInPaise = $basePriceInPaise;
        }

        $offerDiscountInRupees = round($offerDiscountInPaise / 100, 2);
        $offerPriceInRupees = round($offerPriceInPaise / 100, 2);

        // 3. Resolve Coupon (if supplied)
        $hasCoupon = false;
        $coupon = null;
        $couponDiscountInPaise = 0;
        $couponError = null;

        if (! empty($couponCode)) {
            if ($hasOffer && ! $winningOffer->allow_coupons) {
                $couponError = 'Coupons cannot be combined with promotional offer pricing.';
            } else {
                $couponModel = Coupon::where('code', strtoupper(trim($couponCode)))->first();

                if (! $couponModel) {
                    $couponError = 'The coupon code provided is invalid.';
                } elseif (! $couponModel->is_active) {
                    $couponError = 'This coupon is currently inactive.';
                } elseif ($couponModel->isUpcoming()) {
                    $couponError = 'This coupon is not active yet.';
                } elseif ($couponModel->isExpired()) {
                    $couponError = 'This coupon has expired.';
                } elseif ($couponModel->isCourseSpecific() && (! $isCourse || $couponModel->course_id !== $product->id)) {
                    $couponError = 'This coupon is not applicable to the selected product.';
                } elseif ($couponModel->min_order_amount && $offerPriceInPaise < $couponModel->min_order_amount) {
                    $minFormatted = '₹' . number_format($couponModel->min_order_amount / 100, 2);
                    $couponError = "This coupon requires a minimum order value of {$minFormatted}.";
                } elseif ($couponModel->hasReachedGlobalLimit()) {
                    $couponError = 'This coupon has reached its maximum total redemptions.';
                } elseif ($user && $couponModel->hasUserReachedLimit($user)) {
                    $couponError = 'You have already reached the maximum redemption limit for this coupon.';
                } else {
                    // Valid coupon applied to the offer price
                    $hasCoupon = true;
                    $coupon = $couponModel;
                    $couponDiscountInPaise = $couponModel->calculateDiscount($offerPriceInPaise);
                }
            }
        }

        $couponDiscountInRupees = round($couponDiscountInPaise / 100, 2);

        // 4. Final Payable Price
        $finalPriceInPaise = max(0, $offerPriceInPaise - $couponDiscountInPaise);
        $finalPriceInRupees = round($finalPriceInPaise / 100, 2);

        // 5. Total Savings
        $totalSavingsInPaise = $basePriceInPaise - $finalPriceInPaise;
        $totalSavingsInRupees = round($totalSavingsInPaise / 100, 2);
        $savingsPercentage = $basePriceInPaise > 0
            ? (int) round(($totalSavingsInPaise / $basePriceInPaise) * 100)
            : 0;

        return [
            'product' => $product,
            'product_type' => $productType,
            'product_id' => $product->id,
            'base_price' => $basePriceInRupees,
            'base_price_in_paise' => $basePriceInPaise,
            'has_offer' => $hasOffer,
            'offer' => $winningOffer,
            'offer_discount' => $offerDiscountInRupees,
            'offer_discount_in_paise' => $offerDiscountInPaise,
            'offer_price' => $offerPriceInRupees,
            'offer_price_in_paise' => $offerPriceInPaise,
            'has_coupon' => $hasCoupon,
            'coupon' => $coupon,
            'coupon_discount' => $couponDiscountInRupees,
            'coupon_discount_in_paise' => $couponDiscountInPaise,
            'coupon_error' => $couponError,
            'final_price' => $finalPriceInRupees,
            'final_price_in_paise' => $finalPriceInPaise,
            'total_savings' => $totalSavingsInRupees,
            'total_savings_in_paise' => $totalSavingsInPaise,
            'savings_percentage' => $savingsPercentage,
            'formatted_base_price' => '₹' . number_format($basePriceInRupees, 2),
            'formatted_final_price' => '₹' . number_format($finalPriceInRupees, 2),
            'formatted_savings' => '₹' . number_format($totalSavingsInRupees, 2),
        ];
    }

    /**
     * Retrieve the highest priority active promotional offer for a product.
     * Deterministic precedence: Highest priority, then highest discount value, then most recently created.
     */
    public function getWinningOffer(Course|Bundle $product): ?Offer
    {
        $productType = $product instanceof Course ? 'course' : 'bundle';

        return Offer::query()
            ->currentlyValid()
            ->forProduct($productType, $product->id)
            ->orderByDesc('priority')
            ->orderByDesc('discount_value')
            ->orderByDesc('id')
            ->first();
    }
}
