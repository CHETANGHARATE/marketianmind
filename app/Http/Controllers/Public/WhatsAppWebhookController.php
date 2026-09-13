<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppWebhookEvent;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Handle Meta Webhook verification handshake.
     */
    public function verify(Request $request): Response
    {
        $mode = (string) ($request->query('hub_mode', $request->query('hub.mode', '')));
        $token = (string) ($request->query('hub_verify_token', $request->query('hub.verify_token', '')));
        $challenge = (string) ($request->query('hub_challenge', $request->query('hub.challenge', '')));

        $verifiedChallenge = $this->whatsAppService->verifyWebhook($mode, $token, $challenge);

        if ($verifiedChallenge !== null) {
            return response($verifiedChallenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle Meta incoming webhook notifications (delivery receipts, inbound messages).
     */
    public function handle(Request $request): Response
    {
        // 1. Signature Verification
        $signature = $request->header('X-Hub-Signature-256');
        $rawContent = $request->getContent();

        if (! $this->whatsAppService->verifySignature($rawContent, $signature)) {
            Log::warning('WhatsApp webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);
            return response('Invalid signature', 401);
        }

        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = json_decode($rawContent, true) ?? [];
        }

        // 2. Webhook Event Deduplication
        $entries = $payload['entry'] ?? [];
        $isDuplicate = false;

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                if (! empty($value['statuses'])) {
                    foreach ($value['statuses'] as $st) {
                        $eventId = ($st['id'] ?? 'status') . '_' . ($st['status'] ?? '') . '_' . ($st['timestamp'] ?? time());
                        if (WhatsAppWebhookEvent::where('event_id', $eventId)->exists()) {
                            $isDuplicate = true;
                            continue;
                        }
                        WhatsAppWebhookEvent::create([
                            'event_id' => $eventId,
                            'event_type' => 'status_' . ($st['status'] ?? 'unknown'),
                            'payload' => $payload,
                            'processed_at' => now(),
                        ]);
                    }
                }

                if (! empty($value['messages'])) {
                    foreach ($value['messages'] as $msg) {
                        $eventId = $msg['id'] ?? ('msg_' . ($msg['timestamp'] ?? time()));
                        if (WhatsAppWebhookEvent::where('event_id', $eventId)->exists()) {
                            $isDuplicate = true;
                            continue;
                        }
                        WhatsAppWebhookEvent::create([
                            'event_id' => $eventId,
                            'event_type' => 'inbound_message',
                            'payload' => $payload,
                            'processed_at' => now(),
                        ]);
                    }
                }
            }
        }

        // 3. Process payload if not fully duplicated
        if (! $isDuplicate || ! empty($payload['statuses']) || ! empty($payload['messages'])) {
            $this->whatsAppService->processWebhook($payload);
        }

        return response('EVENT_RECEIVED', 200);
    }
}
