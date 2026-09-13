<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Services\PhoneNormalizationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class WhatsAppOptOutController extends Controller
{
    public function __construct(
        protected PhoneNormalizationService $phoneNormalizer
    ) {}

    /**
     * Display the WhatsApp opt-out form.
     */
    public function show(Request $request): View
    {
        $phone = $request->query('phone', '');

        return view('public.whatsapp.opt_out', [
            'phone' => $phone,
        ]);
    }

    /**
     * Handle the WhatsApp opt-out submission.
     */
    public function optOut(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:25'],
        ]);

        $rawPhone = $validated['phone'];
        $normalized = $this->phoneNormalizer->normalize($rawPhone, 'India');

        $updatedCount = 0;

        // Check leads
        $leads = Lead::where('phone', $rawPhone)
            ->orWhere('phone_normalized', $normalized)
            ->orWhere('phone_normalized', $rawPhone)
            ->get();

        foreach ($leads as $lead) {
            $lead->recordWhatsAppOptOut();
            $updatedCount++;
        }

        // Check users
        $users = User::where('phone', $rawPhone)
            ->orWhere('phone_normalized', $normalized)
            ->orWhere('phone_normalized', $rawPhone)
            ->get();

        foreach ($users as $user) {
            $user->recordWhatsAppOptOut();
            $updatedCount++;
        }

        return redirect()->route('whatsapp.opt-out', ['phone' => $rawPhone])
            ->with('status', 'You have been successfully unsubscribed from WhatsApp marketing updates. You will continue to receive critical transactional receipts if applicable.');
    }
}
