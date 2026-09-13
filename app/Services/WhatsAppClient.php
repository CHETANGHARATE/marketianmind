<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppClient
{
    protected bool $enabled;
    protected string $accessToken;
    protected string $phoneNumberId;
    protected string $apiVersion;
    protected string $appSecret;

    public function __construct()
    {
        $this->enabled = (bool) config('whatsapp.enabled', false);
        $this->accessToken = (string) config('whatsapp.access_token', '');
        $this->phoneNumberId = (string) config('whatsapp.phone_number_id', '');
        $this->apiVersion = (string) config('whatsapp.api_version', 'v22.0');
        $this->appSecret = (string) config('whatsapp.app_secret', '');
    }

    /**
     * Determine if WhatsApp service is fully configured with credentials.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->accessToken) && ! empty($this->phoneNumberId);
    }

    /**
     * Determine if WhatsApp service is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->isConfigured();
    }

    /**
     * Send a template-based message via Meta WhatsApp Cloud API.
     *
     * @param string $recipientPhone Digits only format (e.g. 919876543210)
     * @param string $templateName Name of approved template in Meta Manager
     * @param string $languageCode Language code (e.g. 'en', 'en_US', 'hi')
     * @param array $bodyParameters Ordered array of replacement text parameters
     * @return array Result array with 'success', 'provider_message_id', 'error', 'code', 'data'
     */
    public function sendTemplateMessage(
        string $recipientPhone,
        string $templateName,
        string $languageCode = 'en',
        array $bodyParameters = []
    ): array {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'WhatsApp service is disabled or credentials are not configured.',
                'code' => 'SERVICE_DISABLED',
                'provider_message_id' => null,
            ];
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        $templateData = [
            'name' => $templateName,
            'language' => [
                'code' => $languageCode,
            ],
        ];

        if (! empty($bodyParameters)) {
            $params = [];
            foreach ($bodyParameters as $param) {
                $params[] = [
                    'type' => 'text',
                    'text' => (string) $param,
                ];
            }

            $templateData['components'] = [
                [
                    'type' => 'body',
                    'parameters' => $params,
                ],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipientPhone,
            'type' => 'template',
            'template' => $templateData,
        ];

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(12)
                ->acceptJson()
                ->post($url, $payload);

            $data = $response->json() ?? [];

            if ($response->successful() && ! empty($data['messages'][0]['id'])) {
                return [
                    'success' => true,
                    'provider_message_id' => $data['messages'][0]['id'],
                    'error' => null,
                    'code' => null,
                    'data' => $data,
                ];
            }

            $errorMessage = $data['error']['message'] ?? 'Unknown provider error occurred.';
            $errorCode = (string) ($data['error']['code'] ?? $response->status());

            Log::warning('WhatsApp Cloud API returned failure', [
                'code' => $errorCode,
                'error' => $errorMessage,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'provider_message_id' => null,
                'error' => $errorMessage,
                'code' => $errorCode,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp Cloud API HTTP request exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'provider_message_id' => null,
                'error' => 'Network or timeout exception communicating with Meta API.',
                'code' => 'HTTP_EXCEPTION',
                'data' => [],
            ];
        }
    }
}
