<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReferralController extends Controller
{
    /**
     * Display student referral dashboard and history.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $referrals = collect();
        $totalReferrals = 0;
        $convertedReferrals = 0;
        $pendingReferrals = 0;

        if (Schema::hasTable('referrals')) {
            $totalReferrals = $user->referrals()->count();
            $convertedReferrals = $user->referrals()->converted()->count();
            $pendingReferrals = $user->referrals()->registered()->count();

            $referrals = $user->referrals()
                ->with(['referred', 'order.course'])
                ->latest()
                ->paginate(15);
        }

        $referralCode = $user->getReferralCode();
        $referralUrl = $user->referralUrl();

        return view('student.referrals.index', compact(
            'user',
            'referrals',
            'totalReferrals',
            'convertedReferrals',
            'pendingReferrals',
            'referralCode',
            'referralUrl'
        ));
    }
}