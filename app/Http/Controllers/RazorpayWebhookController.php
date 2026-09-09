<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RazorpayWebhookEvent;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    /**
     * Handle incoming asynchronous Razorpay Webhooks.
     */
    public function handle(Request $request, RazorpayService $razorpayService): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        if (! $razorpayService->verifyWebhookSignature($rawPayload, $signature)) {
            Log::warning('Razorpay webhook signature verification failed');
            return response()->json(['error' => 'Invalid webhook signature.'], 400);
        }

        $payload = json_decode($rawPayload, true);

        if (! is_array($payload)) {
            return response()->json(['error' => 'Malformed JSON payload.'], 400);
        }

        $eventId = $request->header('X-Razorpay-Event-Id') ?? ($payload['id'] ?? null);

        if (! $eventId) {
            return response()->json(['error' => 'Missing event ID.'], 400);
        }

        // Idempotency: Ignore already processed events
        $existingEvent = RazorpayWebhookEvent::where('event_id', $eventId)->first();

        if ($existingEvent && $existingEvent->isProcessed()) {
            return response()->json(['status' => 'already_processed'], 200);
        }

        $webhookEvent = $existingEvent ?: RazorpayWebhookEvent::create([
            'event_id' => $eventId,
            'event_type' => $payload['event'] ?? 'unknown',
            'payload' => $payload,
            'status' => 'received',
        ]);

        $eventType = $payload['event'] ?? '';

        try {
            switch ($eventType) {
                case 'order.paid':
                    $this->processOrderPaid($payload);
                    break;

                case 'payment.captured':
                    $this->processPaymentCaptured($payload);
                    break;

                case 'payment.failed':
                    $this->processPaymentFailed($payload);
                    break;

                default:
                    Log::info('Unhandled Razorpay webhook event received', ['event' => $eventType]);
                    break;
            }

            $webhookEvent->markProcessed();
            return response()->json(['status' => 'processed'], 200);
        } catch (\Throwable $e) {
            Log::error('Razorpay webhook processing failed', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            $webhookEvent->markFailed();
            return response()->json(['error' => 'Webhook processing error.'], 500);
        }
    }

    protected function processOrderPaid(array $payload): void
    {
        $orderData = $payload['payload']['order']['entity'] ?? [];
        $razorpayOrderId = $orderData['id'] ?? null;

        if (! $razorpayOrderId) {
            return;
        }

        $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();

        if (! $order) {
            Log::warning('Webhook order.paid received for unknown internal order', ['razorpay_order_id' => $razorpayOrderId]);
            return;
        }

        $paymentData = $payload['payload']['payment']['entity'] ?? [];
        $razorpayPaymentId = $paymentData['id'] ?? null;

        DB::transaction(function () use ($order, $paymentData, $razorpayPaymentId, $razorpayOrderId) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            if ($razorpayPaymentId) {
                Payment::updateOrCreate(
                    ['razorpay_payment_id' => $razorpayPaymentId],
                    [
                        'order_id' => $lockedOrder->id,
                        'user_id' => $lockedOrder->user_id,
                        'course_id' => $lockedOrder->course_id,
                        'razorpay_order_id' => $razorpayOrderId,
                        'amount' => $paymentData['amount'] ?? $lockedOrder->amount,
                        'currency' => $paymentData['currency'] ?? $lockedOrder->currency,
                        'status' => PaymentStatus::CAPTURED,
                        'method' => $paymentData['method'] ?? null,
                        'captured' => true,
                        'paid_at' => now(),
                    ]
                );
            }

            if (! $lockedOrder->isPaid()) {
                $lockedOrder->markPaid();
            }

            $enrollment = Enrollment::query()
                ->where('user_id', $lockedOrder->user_id)
                ->where('course_id', $lockedOrder->course_id)
                ->first();

            if (! $enrollment) {
                Enrollment::create([
                    'user_id' => $lockedOrder->user_id,
                    'course_id' => $lockedOrder->course_id,
                    'status' => EnrollmentStatus::ACTIVE,
                    'enrolled_at' => now(),
                ]);
            } elseif (! $enrollment->isActive() && ! $enrollment->isCompleted()) {
                $enrollment->update([
                    'status' => EnrollmentStatus::ACTIVE,
                    'enrolled_at' => now(),
                ]);
            }
        });
    }

    protected function processPaymentCaptured(array $payload): void
    {
        $paymentData = $payload['payload']['payment']['entity'] ?? [];
        $razorpayOrderId = $paymentData['order_id'] ?? null;
        $razorpayPaymentId = $paymentData['id'] ?? null;

        if (! $razorpayOrderId || ! $razorpayPaymentId) {
            return;
        }

        $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();

        if (! $order) {
            return;
        }

        DB::transaction(function () use ($order, $paymentData, $razorpayPaymentId, $razorpayOrderId) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            Payment::updateOrCreate(
                ['razorpay_payment_id' => $razorpayPaymentId],
                [
                    'order_id' => $lockedOrder->id,
                    'user_id' => $lockedOrder->user_id,
                    'course_id' => $lockedOrder->course_id,
                    'razorpay_order_id' => $razorpayOrderId,
                    'amount' => $paymentData['amount'] ?? $lockedOrder->amount,
                    'currency' => $paymentData['currency'] ?? $lockedOrder->currency,
                    'status' => PaymentStatus::CAPTURED,
                    'method' => $paymentData['method'] ?? null,
                    'captured' => true,
                    'paid_at' => now(),
                ]
            );

            if (! $lockedOrder->isPaid()) {
                $lockedOrder->markPaid();
            }

            $enrollment = Enrollment::query()
                ->where('user_id', $lockedOrder->user_id)
                ->where('course_id', $lockedOrder->course_id)
                ->first();

            if (! $enrollment) {
                Enrollment::create([
                    'user_id' => $lockedOrder->user_id,
                    'course_id' => $lockedOrder->course_id,
                    'status' => EnrollmentStatus::ACTIVE,
                    'enrolled_at' => now(),
                ]);
            }
        });
    }

    protected function processPaymentFailed(array $payload): void
    {
        $paymentData = $payload['payload']['payment']['entity'] ?? [];
        $razorpayOrderId = $paymentData['order_id'] ?? null;
        $razorpayPaymentId = $paymentData['id'] ?? null;

        if (! $razorpayOrderId) {
            return;
        }

        $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();

        if (! $order) {
            return;
        }

        Payment::updateOrCreate(
            ['razorpay_payment_id' => $razorpayPaymentId ?: 'failed_' . uniqid()],
            [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'course_id' => $order->course_id,
                'razorpay_order_id' => $razorpayOrderId,
                'amount' => $paymentData['amount'] ?? $order->amount,
                'currency' => $paymentData['currency'] ?? $order->currency,
                'status' => PaymentStatus::FAILED,
                'failure_code' => $paymentData['error_code'] ?? null,
                'failure_description' => $paymentData['error_description'] ?? null,
            ]
        );

        $order->markFailed();
    }
}