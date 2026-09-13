<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MarketingUnsubscribe;
use Illuminate\Http\Request;

class MarketingUnsubscribeController extends Controller
{
    /**
     * Display the unsubscribe confirmation screen.
     */
    public function show(Request $request)
    {
        $email = strtolower(trim((string) $request->query('email', '')));

        // Check if already unsubscribed
        $isAlreadyUnsubscribed = MarketingUnsubscribe::isUnsubscribed($email);

        return view('public.marketing.unsubscribe', [
            'email' => $email,
            'isAlreadyUnsubscribed' => $isAlreadyUnsubscribed,
            'success' => false,
        ]);
    }

    /**
     * Process the marketing email opt-out request.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $email = strtolower(trim($request->email));
        $reason = $request->input('reason', 'User requested via unsubscribe link');

        MarketingUnsubscribe::recordUnsubscribe($email, $reason);

        return view('public.marketing.unsubscribe', [
            'email' => $email,
            'isAlreadyUnsubscribed' => true,
            'success' => true,
        ]);
    }
}
