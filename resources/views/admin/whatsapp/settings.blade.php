@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.whatsapp.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-400">
                    &larr; WhatsApp Dashboard
                </a>
                <span class="text-xs text-slate-600">/</span>
                <span class="text-xs font-semibold text-emerald-400">Settings &amp; Webhook</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">
                WhatsApp Cloud API Configuration
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Official Meta Business Platform credentials and webhook callback setup. Sensitive tokens are masked.
            </p>
        </div>
    </div>

    <!-- Credentials Status Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6 space-y-6">
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Environment Settings (.env)
        </h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-800 bg-slate-800/40 p-4">
                <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold block">Integration State</span>
                <span class="text-sm font-bold {{ $settings['enabled'] === 'Enabled' ? 'text-emerald-400' : 'text-slate-400' }}">
                    {{ $settings['enabled'] }}
                </span>
                <span class="block text-[11px] font-mono text-slate-500 mt-1">WHATSAPP_ENABLED</span>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-800/40 p-4">
                <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold block">Provider / Version</span>
                <span class="text-sm font-bold text-white">
                    {{ ucfirst($settings['provider']) }} ({{ $settings['api_version'] }})
                </span>
                <span class="block text-[11px] font-mono text-slate-500 mt-1">WHATSAPP_PROVIDER, WHATSAPP_API_VERSION</span>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-800/40 p-4">
                <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold block">Phone Number ID</span>
                <span class="text-sm font-mono text-white">
                    {{ $settings['phone_number_id'] }}
                </span>
                <span class="block text-[11px] font-mono text-slate-500 mt-1">WHATSAPP_PHONE_NUMBER_ID</span>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-800/40 p-4">
                <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold block">Business Account ID</span>
                <span class="text-sm font-mono text-white">
                    {{ $settings['business_account_id'] }}
                </span>
                <span class="block text-[11px] font-mono text-slate-500 mt-1">WHATSAPP_BUSINESS_ACCOUNT_ID</span>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-800/40 p-4">
                <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold block">Permanent Access Token</span>
                <span class="text-sm font-mono text-emerald-400">
                    {{ $settings['access_token'] }}
                </span>
                <span class="block text-[11px] font-mono text-slate-500 mt-1">WHATSAPP_ACCESS_TOKEN</span>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-800/40 p-4">
                <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold block">App Secret (HMAC-SHA256)</span>
                <span class="text-sm font-mono text-emerald-400">
                    {{ $settings['app_secret'] }}
                </span>
                <span class="block text-[11px] font-mono text-slate-500 mt-1">WHATSAPP_APP_SECRET</span>
            </div>
        </div>
    </div>

    <!-- Webhook Setup Guide Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6 space-y-4">
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Meta Webhook Configuration
        </h2>
        <p class="text-xs text-slate-400">
            Configure this callback URL and verification token in your Meta App Dashboard under <strong>WhatsApp &gt; Configuration &gt; Webhook</strong>.
        </p>

        <div class="space-y-3">
            <div>
                <label class="block text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-1">Callback URL</label>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $settings['webhook_url'] }}"
                           class="flex-1 rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-mono text-emerald-400 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-1">Verify Token</label>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $settings['webhook_verify_token'] }}"
                           class="flex-1 rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-mono text-white focus:outline-none">
                </div>
                <span class="block text-[11px] text-slate-500 mt-1">Matches <code>WHATSAPP_WEBHOOK_VERIFY_TOKEN</code> in your environment file.</span>
            </div>

            <div>
                <label class="block text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-1">Webhook Subscription Fields</label>
                <div class="flex flex-wrap gap-2 pt-1">
                    <span class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 text-xs font-mono text-emerald-400">messages</span>
                    <span class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 text-xs font-mono text-emerald-400">message_template_status_update</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
