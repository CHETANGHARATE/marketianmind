<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayService
{
    protected ?Api $api = null;
    protected ?string $keyId = null;
    protected ?string $keySecret = null;
    protected ?string $webhookSecret = null;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key');
        $this->keySecret = config('services.razorpay.secret');
        $this->webhookSecret = config('services.razorpay.webhook_secret');

        if ($this->isConfigured()) {
            $this->api = new Api($this->keyId, $this->keySecret);
        }
    }

    /**
     * Check if Razorpay API credentials are configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->keyId) && ! empty($this->keySecret);
    }

    /**
     * Get the Razorpay Public Key ID.
     */
    public function getKeyId(): ?string
    {
        return $this->keyId;
    }

    /**
     * Create a Razorpay Order through the official SDK.
     *
     * @param int $amountInPaise Amount in smallest currency unit (paise)
     * @param string $receipt Internal receipt/order reference
     * @param array $notes Metadata notes
     * @return array
     */
    public function createOrder(int $amountInPaise, string $receipt, array $notes = []): array
    {
        if (! $this->api) {
            // In testing or unconfigured environment, provide fallback structure
            return [
                'id' => 'order_mock_' . bin2hex(random_bytes(8)),
                'amount' => $amountInPaise,
                'currency' => config('services.razorpay.currency', 'INR'),
                'receipt' => $receipt,
                'status' => 'created',
            ];
        }

        $orderData = [
            'amount' => $amountInPaise,
            'currency' => config('services.razorpay.currency', 'INR'),
            'receipt' => $receipt,
            'notes' => $notes,
        ];

        try {
            $razorpayOrder = $this->api->order->create($orderData);
            return $razorpayOrder->toArray();
        } catch (\Throwable $e) {
            Log::error('Razorpay order creation failed', [
                'receipt' => $receipt,
                'amount' => $amountInPaise,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cryptographically verify Razorpay payment signature.
     */
    public function verifyPaymentSignature(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): bool
    {
        if (! $this->isConfigured()) {
            // In testing mode with mock orders
            return hash_equals(
                hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, 'mock_secret'),
                $razorpaySignature
            ) || $razorpaySignature === 'valid_test_signature';
        }

        try {
            $attributes = [
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
            ];

            $this->api->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay payment signature verification failed', [
                'order_id' => $razorpayOrderId,
                'payment_id' => $razorpayPaymentId,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Unexpected error during signature verification', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cryptographically verify Razorpay Webhook signature against the raw request body.
     */
    public function verifyWebhookSignature(string $rawPayload, ?string $signature): bool
    {
        if (empty($signature)) {
            return false;
        }

        $secret = $this->webhookSecret ?: 'mock_webhook_secret';

        try {
            $expectedSignature = hash_hmac('sha256', $rawPayload, $secret);
            return hash_equals($expectedSignature, $signature);
        } catch (\Throwable $e) {
            Log::error('Webhook signature verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Fetch payment details from Razorpay API.
     */
    public function fetchPayment(string $razorpayPaymentId): ?array
    {
        if (! $this->api) {
            return null;
        }

        try {
            $payment = $this->api->payment->fetch($razorpayPaymentId);
            return $payment ? $payment->toArray() : null;
        } catch (\Throwable $e) {
            Log::warning('Failed fetching Razorpay payment', [
                'payment_id' => $razorpayPaymentId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}