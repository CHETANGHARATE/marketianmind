<?php

namespace App\Services;

use App\Enums\ReferralStatus;
use App\Models\Order;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\ReferralConvertedNotification;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReferralService
{
    public const COOKIE_NAME = 'marketian_referral_code';
    public const COOKIE_LIFETIME = 43200; // 30 days in minutes

    /**
     * Capture referral code into session and cookie.
     */
    public function captureReferralCode(string $code): void
    {
        $cleanCode = strtoupper(trim($code));
        if (! empty($cleanCode)) {
            session(['referral_code' => $cleanCode]);
            Cookie::queue(self::COOKIE_NAME, $cleanCode, self::COOKIE_LIFETIME);
        }
    }

    /**
     * Get active referral code from request, session, or cookie.
     */
    public function getActiveReferralCode(?string $explicitCode = null): ?string
    {
        if (! empty($explicitCode)) {
            return strtoupper(trim($explicitCode));
        }

        $sessionCode = session('referral_code');
        if (! empty($sessionCode)) {
            return strtoupper(trim($sessionCode));
        }

        $cookieCode = request()->cookie(self::COOKIE_NAME);
        if (! empty($cookieCode)) {
            return strtoupper(trim($cookieCode));
        }

        return null;
    }

    /**
     * Clear captured referral cookies and session.
     */
    public function clearReferralCode(): void
    {
        session()->forget('referral_code');
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    /**
     * Attribute a registered user to a referring user.
     * Enforces:
     * 1. Valid referral code.
     * 2. Prevention of self-referral.
     * 3. Prevention of duplicate attribution (one referral per user).
     */
    public function attributeRegistration(User $referredUser, ?string $code = null, ?string $ipAddress = null): ?Referral
    {
        if (! Schema::hasTable('referrals')) {
            return null;
        }

        $referralCode = $this->getActiveReferralCode($code);
        if (empty($referralCode)) {
            return null;
        }

        // Prevent duplicate attribution: check if user is already referred
        if (Referral::where('referred_id', $referredUser->id)->exists()) {
            $this->clearReferralCode();
            return null;
        }

        // Lookup referrer user
        $referrer = User::where('referral_code', $referralCode)->first();
        if (! $referrer) {
            $this->clearReferralCode();
            return null;
        }

        // Prevent self-referral
        if ($referrer->id === $referredUser->id) {
            Log::warning('Self-referral attempt prevented', [
                'user_id' => $referredUser->id,
                'code' => $referralCode,
            ]);
            $this->clearReferralCode();
            return null;
        }

        // Create referral attribution
        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referredUser->id,
            'referral_code' => $referralCode,
            'status' => ReferralStatus::REGISTERED,
            'ip_address' => $ipAddress ?? request()->ip(),
        ]);

        $this->clearReferralCode();

        return $referral;
    }

    /**
     * Attribute conversion when an order is paid.
     */
    public function attributeConversion(Order $order): ?Referral
    {
        if (! Schema::hasTable('referrals')) {
            return null;
        }

        $referral = Referral::where('referred_id', $order->user_id)
            ->where('status', ReferralStatus::REGISTERED->value)
            ->first();

        if (! $referral) {
            return null;
        }

        // Prevent self-referral on conversion as well
        if ($referral->referrer_id === $order->user_id) {
            return null;
        }

        $referral->markConverted($order);

        // Notify referrer if referrer exists
        if ($referral->referrer) {
            try {
                $referral->referrer->notify(new ReferralConvertedNotification($referral, $order));
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch referral conversion notification', [
                    'referral_id' => $referral->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $referral;
    }
}