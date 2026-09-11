<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\TransactionalMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailController extends Controller
{
    /**
     * Display mail configuration status and SMTP connectivity test form.
     */
    public function index(): View
    {
        $mailConfig = [
            'mailer' => config('mail.default', 'log'),
            'host' => config('mail.mailers.smtp.host', '127.0.0.1'),
            'port' => config('mail.mailers.smtp.port', 2525),
            'username' => config('mail.mailers.smtp.username') ? config('mail.mailers.smtp.username') : 'Not Configured',
            'has_password' => ! empty(config('mail.mailers.smtp.password')),
            'from_address' => config('mail.from.address', 'hello@example.com'),
            'from_name' => config('mail.from.name', 'Marketian Mind'),
            'encryption' => config('mail.mailers.smtp.encryption') ?? config('mail.mailers.smtp.scheme') ?? 'Auto / Default',
        ];

        return view('admin.mail.index', compact('mailConfig'));
    }

    /**
     * Dispatch a test transactional email.
     */
    public function sendTest(Request $request, TransactionalMailService $mailService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $success = $mailService->sendTestMail($validated['email']);

        AuditLogger::log(
            'mail.test_sent',
            'Mail',
            "Dispatched SMTP test email to {$validated['email']} (Result: " . ($success ? 'Delivered' : 'Failed') . ")."
        );

        if ($success) {
            return back()->with('status', "Test email successfully sent to {$validated['email']}. Check your inbox or spam folder.");
        }

        return back()->with('error', "Test email delivery failed. Please review your Hostinger SMTP credentials in .env or inspect storage/logs/laravel.log.");
    }
}