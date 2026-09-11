@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Mail &amp; SMTP Configuration
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Inspect transactional mail parameters and verify SMTP delivery for student communication.
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 text-xs font-semibold text-rose-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Configuration Summary Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Active Mail Configuration</h2>
                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700">
                    {{ strtoupper($mailConfig['mailer']) }} Driver
                </span>
            </div>

            <div class="space-y-3 text-xs">
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Default Transport</span>
                    <span class="font-bold text-slate-900">{{ $mailConfig['mailer'] }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">SMTP Host</span>
                    <span class="font-bold text-slate-900">{{ $mailConfig['host'] }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">SMTP Port</span>
                    <span class="font-bold text-slate-900">{{ $mailConfig['port'] }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">SMTP Username</span>
                    <span class="font-bold text-slate-900">{{ $mailConfig['username'] }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">SMTP Password</span>
                    <span class="font-bold text-slate-900">
                        {{ $mailConfig['has_password'] ? '•••••••• (Configured via .env)' : 'Not Configured' }}
                    </span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Sender Name</span>
                    <span class="font-bold text-slate-900">{{ $mailConfig['from_name'] }}</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-500 font-medium">Sender Address</span>
                    <span class="font-bold text-indigo-600">{{ $mailConfig['from_address'] }}</span>
                </div>
            </div>

            <div class="mt-4 rounded-xl bg-slate-50 p-3.5 border border-slate-200/80 text-[11px] text-slate-500 leading-relaxed">
                <strong class="font-bold text-slate-700">Hostinger Premium Shared Hosting Note:</strong><br>
                To configure live emails, update your <code class="bg-white px-1 py-0.5 rounded border text-slate-700">.env</code> with Hostinger email credentials:
                <br>
                <code class="block mt-1 bg-white p-2 rounded border border-slate-200 font-mono text-[10px] text-slate-800">
                    MAIL_MAILER=smtp<br>
                    MAIL_HOST=smtp.hostinger.com<br>
                    MAIL_PORT=465<br>
                    MAIL_USERNAME=support@yourdomain.com<br>
                    MAIL_PASSWORD=your_email_password<br>
                    MAIL_ENCRYPTION=ssl<br>
                    MAIL_FROM_ADDRESS="support@yourdomain.com"<br>
                    MAIL_FROM_NAME="Marketian Mind"
                </code>
            </div>
        </div>

        <!-- SMTP Test Form Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="border-b border-slate-100 pb-3 mb-4">
                    <h2 class="text-sm font-bold text-slate-900">Send Test Email</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Dispatches a live transactional email to verify outbound SMTP connectivity.</p>
                </div>

                <form action="{{ route('admin.mail.test') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                            Recipient Email Address <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ auth()->user()->email }}" required placeholder="your.email@example.com" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('email')
                            <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Dispatch Test Email
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-4 text-xs text-slate-500">
                <p class="leading-relaxed">
                    Test events are registered in the <a href="{{ route('admin.audit_logs.index') }}" class="text-indigo-600 underline font-semibold">Audit Logs</a> with timestamp, recipient address, and dispatch status.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection