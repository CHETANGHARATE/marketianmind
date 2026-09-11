@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">SMTP Connection Successful! ✅</h1>
    <p class="text">
        This is a test email dispatched from your Marketian Mind administration control panel to verify your transactional mail setup.
    </p>

    <div class="info-panel">
        <div class="info-row">
            <span class="info-label">Recipient</span>
            <span class="info-value">{{ $recipientEmail }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Mail Driver</span>
            <span class="info-value">{{ config('mail.default') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">From Address</span>
            <span class="info-value">{{ config('mail.from.address') }} ({{ config('mail.from.name') }})</span>
        </div>
        <div class="info-row">
            <span class="info-label">Timestamp</span>
            <span class="info-value">{{ now()->toDateTimeString() }}</span>
        </div>
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        Transactional email functionality is operating properly on your hosting server.
    </p>
@endsection