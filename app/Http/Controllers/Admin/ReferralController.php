<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReferralController extends Controller
{
    /**
     * Display admin overview of all referrals.
     */
    public function index(Request $request): View
    {
        $totalCount = 0;
        $convertedCount = 0;
        $registeredCount = 0;
        $conversionRate = 0;
        $uniqueReferrersCount = 0;
        $referrals = collect();

        if (Schema::hasTable('referrals')) {
            $totalCount = Referral::count();
            $convertedCount = Referral::converted()->count();
            $registeredCount = Referral::registered()->count();
            $conversionRate = $totalCount > 0 ? round(($convertedCount / $totalCount) * 100, 1) : 0;
            $uniqueReferrersCount = Referral::distinct('referrer_id')->count('referrer_id');

            $query = Referral::query()->with(['referrer', 'referred', 'order.course']);

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            if ($request->filled('search')) {
                $search = trim($request->query('search'));
                $query->where(function ($q) use ($search) {
                    $q->where('referral_code', 'like', "%{$search}%")
                        ->orWhereHas('referrer', function ($rq) use ($search) {
                            $rq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('referred', function ($rq) use ($search) {
                            $rq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $referrals = $query->latest()->paginate(20)->withQueryString();
        }

        return view('admin.referrals.index', compact(
            'referrals',
            'totalCount',
            'convertedCount',
            'registeredCount',
            'conversionRate',
            'uniqueReferrersCount'
        ));
    }
}