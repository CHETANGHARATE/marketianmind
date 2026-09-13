<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Integration Status
    |--------------------------------------------------------------------------
    */
    'enabled' => (bool) env('WHATSAPP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Provider
    |--------------------------------------------------------------------------
    */
    'provider' => env('WHATSAPP_PROVIDER', 'meta'),

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API Credentials
    |--------------------------------------------------------------------------
    */
    'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
    'api_version' => env('WHATSAPP_API_VERSION', 'v22.0'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Verification & Security
    |--------------------------------------------------------------------------
    */
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),
    'app_secret' => env('WHATSAPP_APP_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Phone Number Normalization Defaults
    |--------------------------------------------------------------------------
    */
    'default_country' => env('WHATSAPP_DEFAULT_COUNTRY', 'India'),
];
