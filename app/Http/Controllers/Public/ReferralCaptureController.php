<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralCaptureController extends Controller
{
    /**
     * Capture referral code from URL and redirect visitor.
     */
    public function capture(string $code, Request $request): RedirectResponse
    {
        $cleanCode = strtoupper(trim($code));

        if (! empty($cleanCode) && strlen($cleanCode) <= 32) {
            app(ReferralService::class)->captureReferralCode($cleanCode);
        }

        if (Auth::check()) {
            return redirect()->route('student.dashboard')->with('info', 'You are already registered and logged in.');
        }

        return redirect()->route('register', ['ref' => $cleanCode]);
    }
}