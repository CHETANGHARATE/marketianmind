<?php

namespace App\Services;

use App\Enums\WhatsAppMessageStatus;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Models\WhatsAppWebhookEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class WhatsAppService
{
    public function __construct(
        protected WhatsAppClient $client,
        protected PhoneNormalizationService $phoneService
    ) {}

    /**
     * Send a template-based message to a recipient (User or Lead).
     */
    public function sendTemplateMessage(
        Model $recipient,
        WhatsAppTemplate|string $template,
        array $context = [],
        ?string $idempotencyKey = null,
        bool $isMarketing = false,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): WhatsAppMessage {
        // Idempotency check: if message with this key already exists, return it
        if (! empty($idempotencyKey)) {
            $existing = WhatsAppMessage::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        $userId = ($recipient instanceof User) ? $recipient->id : null;
        $leadId = ($recipient instanceof Lead) ? $recipient->id : null;
        $rawPhone = $recipient->phone ?? '';

        // Resolve template
        $templateModel = $template instanceof WhatsAppTemplate
            ? $template
            : WhatsAppTemplate::where('slug', $template)
                ->orWhere('provider_template_name', $template)
                ->first();

        $templateName = $templateModel ? ($templateModel->provider_template_name ?? $templateModel->template_name) : (is_string($template) ? $template : 'unknown');
        $templateLanguage = $templateModel ? $templateModel->language : 'en';
        $isMarketingMessage = $isMarketing || ($templateModel && $templateModel->isMarketing());

        // Validate and normalize phone
        $country = $recipient->country ?? config('whatsapp.default_country', 'India');
        $normalizedPhone = $this->phoneService->normalize($rawPhone, $country);

        if (! config('whatsapp.enabled', false)) {
            return new WhatsAppMessage([
                'user_id' => $userId,
                'lead_id' => $leadId,
                'phone_number' => $rawPhone ?: 'unknown',
                'phone_normalized' => $normalizedPhone ?: 'unknown',
                'direction' => 'outbound',
                'message_type' => 'template',
                'template_id' => $templateModel?->id,
                'template_name' => $templateName,
                'template_language' => $templateLanguage,
                'idempotency_key' => $idempotencyKey,
                'is_marketing' => $isMarketingMessage,
                'metadata' => $context,
                'status' => WhatsAppMessageStatus::CANCELLED,
                'error_code' => 'WHATSAPP_DISABLED',
                'error_message' => 'WhatsApp integration is currently disabled in system configuration.',
                'failed_at' => now(),
            ]);
        }

        $messageRecord = new WhatsAppMessage([
            'user_id' => $userId,
            'lead_id' => $leadId,
            'phone_number' => $rawPhone ?: 'unknown',
            'phone_normalized' => $normalizedPhone ?: 'unknown',
            'direction' => 'outbound',
            'message_type' => 'template',
            'template_id' => $templateModel?->id,
            'template_name' => $templateName,
            'template_language' => $templateLanguage,
            'idempotency_key' => $idempotencyKey,
            'is_marketing' => $isMarketingMessage,
            'metadata' => $context,
        ]);

        if (empty($normalizedPhone)) {
            $messageRecord->status = WhatsAppMessageStatus::FAILED;
            $messageRecord->error_code = 'INVALID_PHONE';
            $messageRecord->error_message = 'Recipient phone number is missing or cannot be normalized to E.164 standard.';
            $messageRecord->failed_at = now();
            $messageRecord->save();

            return $messageRecord;
        }

        if (! $templateModel) {
            $messageRecord->status = WhatsAppMessageStatus::FAILED;
            $messageRecord->error_code = 'TEMPLATE_NOT_FOUND';
            $messageRecord->error_message = "WhatsApp template '{$templateName}' does not exist.";
            $messageRecord->failed_at = now();
            $messageRecord->save();

            return $messageRecord;
        }

        // Permission & Consent check for Marketing messages
        if ($isMarketingMessage && (! method_exists($recipient, 'hasWhatsAppOptIn') || ! $recipient->hasWhatsAppOptIn())) {
            $messageRecord->status = WhatsAppMessageStatus::CANCELLED;
            $messageRecord->error_code = 'NO_MARKETING_OPT_IN';
            $messageRecord->error_message = 'Recipient has not opted in or has opted out of WhatsApp marketing communications.';
            $messageRecord->failed_at = now();

            return $messageRecord;
        }

        // Resolve template parameters
        $bodyParameters = $this->resolveTemplateParameters($templateModel, $recipient, $context);

        // Format phone for Meta API
        $metaPhone = $this->phoneService->toMetaFormat($normalizedPhone, $country);

        // Dispatch via WhatsAppClient
        $result = $this->client->sendTemplateMessage(
            recipientPhone: $metaPhone,
            templateName: $templateModel->provider_template_name,
            languageCode: $templateModel->language,
            bodyParameters: $bodyParameters
        );

        if ($result['success']) {
            $messageRecord->status = WhatsAppMessageStatus::SENT;
            $messageRecord->provider_message_id = $result['provider_message_id'];
            $messageRecord->sent_at = now();
            $messageRecord->error_code = null;
            $messageRecord->error_message = null;
        } else {
            $messageRecord->status = WhatsAppMessageStatus::FAILED;
            $messageRecord->error_code = $result['code'] ?? 'SEND_FAILED';
            $messageRecord->error_message = $result['error'] ?? 'WhatsApp API delivery error.';
            $messageRecord->failed_at = now();
        }

        $messageRecord->save();

        return $messageRecord;
    }

    /**
     * Resolve ordered template parameters using allowlisted variables.
     */
    public function resolveTemplateParameters(WhatsAppTemplate $template, Model $recipient, array $context = []): array
    {
        $variableDefinitions = $template->variables ?? [];
        if (empty($variableDefinitions) || ! is_array($variableDefinitions)) {
            return [];
        }

        $parameters = [];
        foreach ($variableDefinitions as $varKey) {
            $parameters[] = $this->resolveAllowlistedVariable((string) $varKey, $recipient, $context);
        }

        return $parameters;
    }

    /**
     * Interpolate template body with allowlisted variables safely.
     */
    public function interpolateTemplateBody(WhatsAppTemplate $template, Model $recipient, array $context = []): string
    {
        $body = $template->body_text ?? $template->body ?? '';

        // Replace any allowlisted variables {{ variable.name }}
        $interpolated = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($matches) use ($recipient, $context) {
            return $this->resolveAllowlistedVariable($matches[1], $recipient, $context);
        }, $body);

        // Strip any remaining unmapped or suspicious code tags {{ ... }}
        return preg_replace('/\{\{[\s\S]*?\}\}/', '', $interpolated);
    }

    /**
     * Allowlist resolver for safe variables. Zero eval, zero dynamic execution.
     */
    public function resolveAllowlistedVariable(string $varKey, Model $recipient, array $context = []): string
    {
        $cleanKey = trim(str_replace(['{{', '}}', ' '], '', $varKey));

        // 1. Lead variables
        if ($recipient instanceof Lead) {
            switch ($cleanKey) {
                case 'lead.name':
                    return (string) ($recipient->name ?? 'Learner');
                case 'lead.email':
                    return (string) ($recipient->email ?? '');
                case 'lead.phone':
                    return (string) ($recipient->phone ?? '');
                case 'lead.company_name':
                    return (string) ($recipient->company_name ?? 'your business');
                case 'lead.status':
                    return $recipient->status instanceof \BackedEnum ? $recipient->status->value : (string) $recipient->status;
            }
        }

        // 2. User variables
        if ($recipient instanceof User) {
            switch ($cleanKey) {
                case 'user.name':
                    return (string) ($recipient->name ?? 'Student');
                case 'user.email':
                    return (string) ($recipient->email ?? '');
                case 'user.phone':
                    return (string) ($recipient->phone ?? '');
            }
        }

        // 3. Course variables
        if (! empty($context['course_id'])) {
            $course = Course::find($context['course_id']);
            if ($course) {
                if ($cleanKey === 'course.title') {
                    return $course->title;
                }
                if ($cleanKey === 'course.url') {
                    return route('courses.show', $course->slug);
                }
            }
        }

        // 4. Bundle variables
        if (! empty($context['bundle_id'])) {
            $bundle = Bundle::find($context['bundle_id']);
            if ($bundle) {
                if ($cleanKey === 'bundle.title') {
                    return $bundle->title;
                }
                if ($cleanKey === 'bundle.url') {
                    return route('bundles.show', $bundle->slug);
                }
            }
        }

        // 5. Explicit context matching
        if (array_key_exists($cleanKey, $context) && is_scalar($context[$cleanKey])) {
            return (string) $context[$cleanKey];
        }

        // 6. Opt-out link
        if ($cleanKey === 'opt_out_url') {
            $phone = $recipient->phone_normalized ?? $recipient->phone ?? '';
            return route('whatsapp.opt-out', ['phone' => $phone]);
        }

        return '';
    }

    /**
     * Verify Meta Webhook Challenge.
     */
    public function verifyWebhook(string $mode, string $token, string $challenge): ?string
    {
        $expectedToken = config('whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && ! empty($expectedToken) && hash_equals($expectedToken, $token)) {
            return $challenge;
        }

        return null;
    }

    /**
     * Verify Meta Webhook HMAC-SHA256 signature header.
     */
    public function verifySignature(string $rawPayload, ?string $signatureHeader): bool
    {
        $appSecret = config('whatsapp.app_secret');

        // If secret is not configured in local/test environment, pass
        if (empty($appSecret)) {
            return true;
        }

        if (empty($signatureHeader) || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $rawPayload, $appSecret);
        $providedSignature = substr($signatureHeader, 7);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Process an incoming Meta WhatsApp Webhook payload.
     */
    public function processWebhook(array $payload): array
    {
        $stats = [
            'statuses_updated' => 0,
            'inbound_received' => 0,
        ];

        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                // 1. Process Delivery Status Updates
                if (! empty($value['statuses']) && is_array($value['statuses'])) {
                    foreach ($value['statuses'] as $statusUpdate) {
                        $wamid = $statusUpdate['id'] ?? null;
                        $statusStr = $statusUpdate['status'] ?? null;
                        $timestamp = ! empty($statusUpdate['timestamp']) ? (int) $statusUpdate['timestamp'] : time();

                        if ($wamid && $statusStr) {
                            $message = WhatsAppMessage::where('provider_message_id', $wamid)->first();
                            if ($message) {
                                $this->updateMessageStatus($message, $statusStr, $timestamp, $statusUpdate['errors'] ?? []);
                                $stats['statuses_updated']++;
                            }
                        }
                    }
                }

                // 2. Process Inbound Messages
                if (! empty($value['messages']) && is_array($value['messages'])) {
                    foreach ($value['messages'] as $inbound) {
                        $wamid = $inbound['id'] ?? null;
                        $from = $inbound['from'] ?? null;
                        $type = $inbound['type'] ?? 'text';
                        $body = $inbound['text']['body'] ?? null;
                        $timestamp = ! empty($inbound['timestamp']) ? (int) $inbound['timestamp'] : time();

                        if ($wamid && $from) {
                            $normalizedFrom = $this->phoneService->normalize($from, 'India');

                            // Find recipient by phone
                            $lead = Lead::where('phone_normalized', $normalizedFrom)->first();
                            $user = User::where('phone_normalized', $normalizedFrom)->first();

                            WhatsAppMessage::firstOrCreate(
                                ['provider_message_id' => $wamid],
                                [
                                    'user_id' => $user?->id,
                                    'lead_id' => $lead?->id,
                                    'phone_number' => $from,
                                    'phone_normalized' => $normalizedFrom ?: $from,
                                    'direction' => 'inbound',
                                    'message_type' => $type,
                                    'status' => WhatsAppMessageStatus::DELIVERED,
                                    'metadata' => [
                                        'body' => $body,
                                        'raw' => $inbound,
                                    ],
                                    'delivered_at' => date('Y-m-d H:i:s', $timestamp),
                                ]
                            );

                            $stats['inbound_received']++;
                        }
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Transition a message record to a new status from webhook event.
     */
    protected function updateMessageStatus(WhatsAppMessage $message, string $metaStatus, int $timestamp, array $errors = []): void
    {
        $dt = date('Y-m-d H:i:s', $timestamp);

        switch ($metaStatus) {
            case 'sent':
                if ($message->status !== WhatsAppMessageStatus::DELIVERED && $message->status !== WhatsAppMessageStatus::READ) {
                    $message->status = WhatsAppMessageStatus::SENT;
                }
                $message->sent_at = $message->sent_at ?? $dt;
                break;

            case 'delivered':
                if ($message->status !== WhatsAppMessageStatus::READ) {
                    $message->status = WhatsAppMessageStatus::DELIVERED;
                }
                $message->delivered_at = $message->delivered_at ?? $dt;
                break;

            case 'read':
                $message->status = WhatsAppMessageStatus::READ;
                $message->read_at = $message->read_at ?? $dt;
                break;

            case 'failed':
                $message->status = WhatsAppMessageStatus::FAILED;
                $message->failed_at = $message->failed_at ?? $dt;
                if (! empty($errors[0])) {
                    $message->error_code = (string) ($errors[0]['code'] ?? 'DELIVERY_FAILED');
                    $message->error_message = (string) ($errors[0]['message'] ?? 'Delivery failed at provider.');
                }
                break;
        }

        $message->save();
    }
}
