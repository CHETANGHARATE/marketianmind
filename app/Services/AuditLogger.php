<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Sensitive field keys that must NEVER be stored in audit logs.
     *
     * @var array<int, string>
     */
    protected static array $sensitiveFields = [
        'password',
        'password_confirmation',
        'remember_token',
        'api_key',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        'razorpay_secret',
        'razorpay_key_secret',
        'razorpay_signature',
        'webhook_secret',
        'card_number',
        'cvv',
        'cvc',
        'pin',
        'app_key',
    ];

    /**
     * Record an audit log for an administrative state change.
     *
     * @param string $action Action verb ('created', 'updated', 'published', 'unpublished', 'deleted')
     * @param Model|string $auditable The affected resource model or type string
     * @param string $description Clear human-readable description of what changed
     * @param array<string, mixed>|null $oldValues Original attribute values before change
     * @param array<string, mixed>|null $newValues Updated attribute values after change
     * @param User|null $actor The user who performed the action (defaults to auth()->user())
     * @param string|null $resourceLabel Safe snapshot of the resource name/title
     */
    public static function log(
        string $action,
        Model|string $auditable,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $actor = null,
        ?string $resourceLabel = null
    ): AuditLog {
        $currentUser = $actor ?? auth()->user();

        $auditableType = is_string($auditable) ? $auditable : class_basename($auditable);
        $auditableId = $auditable instanceof Model ? $auditable->getKey() : null;

        if ($resourceLabel === null && $auditable instanceof Model) {
            $resourceLabel = $auditable->title ?? $auditable->name ?? null;
        }

        $sanitizedOld = $oldValues !== null ? static::sanitizeValues($oldValues) : null;
        $sanitizedNew = $newValues !== null ? static::sanitizeValues($newValues) : null;

        // Capture request context safely if in HTTP request lifecycle
        $ipAddress = request()?->ip();
        $userAgent = request()?->userAgent();

        return AuditLog::create([
            'user_id' => $currentUser?->id,
            'admin_name' => $currentUser?->name ?? 'System',
            'action' => strtolower($action),
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'resource_label' => $resourceLabel,
            'description' => $description,
            'old_values' => ! empty($sanitizedOld) ? $sanitizedOld : null,
            'new_values' => ! empty($sanitizedNew) ? $sanitizedNew : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Recursively strip out sensitive fields before persistence.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function sanitizeValues(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Check if key matches sensitive patterns
            $normalizedKey = strtolower(str_replace(['-', '_'], '', (string) $key));

            $isSensitive = false;
            foreach (static::$sensitiveFields as $sensitive) {
                $normalizedSensitive = strtolower(str_replace(['-', '_'], '', $sensitive));
                if ($normalizedKey === $normalizedSensitive || str_contains($normalizedKey, $normalizedSensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                continue; // Completely exclude sensitive field
            }

            if (is_array($value)) {
                $sanitized[$key] = static::sanitizeValues($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}