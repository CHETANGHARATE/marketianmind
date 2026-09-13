<?php

namespace App\Services;

use App\Enums\ConversionEventName;
use App\Models\ConversionEvent;
use App\Models\ExperimentExposure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConversionTrackingService
{
    public const COOKIE_NAME = 'mm_exp_id';
    public const COOKIE_LIFETIME = 43200; // 30 days in minutes

    /**
     * Get or create a persistent anonymous experiment identifier.
     */
    public function getAnonymousId(?Request $request = null): string
    {
        $req = $request ?: request();

        if ($req && $req->hasCookie(self::COOKIE_NAME)) {
            return (string) $req->cookie(self::COOKIE_NAME);
        }

        $sessionVal = session(self::COOKIE_NAME);
        if ($sessionVal) {
            return (string) $sessionVal;
        }

        $anonId = 'exp_' . Str::random(24);
        session([self::COOKIE_NAME => $anonId]);

        try {
            Cookie::queue(self::COOKIE_NAME, $anonId, self::COOKIE_LIFETIME);
        } catch (\Throwable) {
            // Safe fallback if called outside HTTP cycle
        }

        return $anonId;
    }

    /**
     * Generate a deterministic 64-char SHA-256 visitor hash for identity and exposures.
     */
    public function getVisitorHash(?User $user = null, ?string $anonymousId = null): string
    {
        $currentUser = $user ?: auth()->user();

        if ($currentUser) {
            return hash('sha256', 'user:' . $currentUser->id);
        }

        $anon = $anonymousId ?: $this->getAnonymousId();
        return hash('sha256', 'anon:' . $anon);
    }

    /**
     * Track a conversion event server-side with fail-safe error isolation.
     */
    public function track(string|ConversionEventName $event, array $data = []): ?ConversionEvent
    {
        try {
            $eventName = $event instanceof ConversionEventName ? $event->value : (string) $event;

            $user = isset($data['user_id']) ? User::find($data['user_id']) : auth()->user();
            $anonymousId = $data['anonymous_id'] ?? $this->getAnonymousId();
            $visitorHash = $this->getVisitorHash($user, $anonymousId);

            $experimentId = $data['experiment_id'] ?? null;
            $variantId = $data['variant_id'] ?? null;

            // Automatic attribution to latest exposure if not explicitly set
            if (!$experimentId && !$variantId) {
                $exposure = ExperimentExposure::where('visitor_hash', $visitorHash)
                    ->latest('occurred_at')
                    ->first();

                if ($exposure) {
                    $experimentId = $exposure->experiment_id;
                    $variantId = $exposure->variant_id;
                }
            }

            // Sanitize metadata to prevent leaking secrets or passwords
            $metadata = $data['metadata'] ?? [];
            if (is_array($metadata)) {
                $sensitiveKeys = ['password', 'secret', 'token', 'key', 'card', 'cvv', 'signature'];
                foreach ($sensitiveKeys as $key) {
                    unset($metadata[$key]);
                }
            }

            return ConversionEvent::create([
                'event_name' => $eventName,
                'user_id' => $user?->id,
                'lead_id' => $data['lead_id'] ?? null,
                'session_id' => session()->isStarted() ? session()->getId() : null,
                'anonymous_id' => $anonymousId,
                'course_id' => $data['course_id'] ?? null,
                'bundle_id' => $data['bundle_id'] ?? null,
                'experiment_id' => $experimentId,
                'variant_id' => $variantId,
                'url_path' => $data['url_path'] ?? (request() ? request()->path() : null),
                'referrer' => $data['referrer'] ?? (request() ? Str::limit(request()->headers->get('referer'), 500) : null),
                'metadata' => !empty($metadata) ? $metadata : null,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ConversionTrackingService: Event recording failed', [
                'event' => is_string($event) ? $event : $event->value,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Compute end-to-end conversion funnel metrics with step-by-step drop-offs.
     */
    public function getFunnelMetrics(?Carbon $start = null, ?Carbon $end = null, ?int $courseId = null, ?int $bundleId = null): array
    {
        $baseQuery = ConversionEvent::query()
            ->when($start, fn($q) => $q->where('occurred_at', '>=', $start))
            ->when($end, fn($q) => $q->where('occurred_at', '<=', $end))
            ->when($courseId, fn($q) => $q->where('course_id', $courseId))
            ->when($bundleId, fn($q) => $q->where('bundle_id', $bundleId));

        $views = (clone $baseQuery)->whereIn('event_name', ['course_view', 'bundle_view'])->count();
        $ctaClicks = (clone $baseQuery)->whereIn('event_name', ['course_cta_click', 'bundle_cta_click'])->count();
        $leads = (clone $baseQuery)->where('event_name', 'lead_created')->count();
        $checkoutStarts = (clone $baseQuery)->where('event_name', 'checkout_started')->count();
        $paymentSuccess = (clone $baseQuery)->where('event_name', 'payment_success')->count();
        $enrollments = (clone $baseQuery)->whereIn('event_name', ['course_enrolled', 'bundle_purchased'])->count();

        // Calculate sequential step conversions and drop-offs
        $stages = [
            [
                'name' => 'Catalog & Detail Views',
                'stage' => 'Catalog & Detail Views',
                'count' => $views,
                'drop_off_pct' => $views > 0 ? round((($views - $ctaClicks) / $views) * 100, 1) : 0.0,
                'conversion_pct' => $views > 0 ? round(($ctaClicks / $views) * 100, 1) : 0.0,
                'drop_off_from_previous' => 0.0,
                'conversion_from_previous' => 100.0,
            ],
            [
                'name' => 'CTA Clicks (Intent)',
                'stage' => 'CTA Clicks (Intent)',
                'count' => $ctaClicks,
                'drop_off_pct' => $ctaClicks > 0 ? round((($ctaClicks - $checkoutStarts) / $ctaClicks) * 100, 1) : 0.0,
                'conversion_pct' => $ctaClicks > 0 ? round(($checkoutStarts / $ctaClicks) * 100, 1) : 0.0,
                'drop_off_from_previous' => $views > 0 ? round((($views - $ctaClicks) / $views) * 100, 1) : 0.0,
                'conversion_from_previous' => $views > 0 ? round(($ctaClicks / $views) * 100, 1) : 0.0,
            ],
            [
                'name' => 'Checkout Started',
                'stage' => 'Checkout Started',
                'count' => $checkoutStarts,
                'drop_off_pct' => $checkoutStarts > 0 ? round((($checkoutStarts - $paymentSuccess) / $checkoutStarts) * 100, 1) : 0.0,
                'conversion_pct' => $checkoutStarts > 0 ? round(($paymentSuccess / $checkoutStarts) * 100, 1) : 0.0,
                'drop_off_from_previous' => $ctaClicks > 0 ? round((($ctaClicks - $checkoutStarts) / $ctaClicks) * 100, 1) : 0.0,
                'conversion_from_previous' => $ctaClicks > 0 ? round(($checkoutStarts / $ctaClicks) * 100, 1) : 0.0,
            ],
            [
                'name' => 'Payment Completed',
                'stage' => 'Payment Completed',
                'count' => $paymentSuccess,
                'drop_off_pct' => $paymentSuccess > 0 ? round((($paymentSuccess - $enrollments) / $paymentSuccess) * 100, 1) : 0.0,
                'conversion_pct' => $paymentSuccess > 0 ? round(($enrollments / $paymentSuccess) * 100, 1) : 0.0,
                'drop_off_from_previous' => $checkoutStarts > 0 ? round((($checkoutStarts - $paymentSuccess) / $checkoutStarts) * 100, 1) : 0.0,
                'conversion_from_previous' => $checkoutStarts > 0 ? round(($paymentSuccess / $checkoutStarts) * 100, 1) : 0.0,
            ],
            [
                'name' => 'Course/Bundle Enrolled',
                'stage' => 'Course/Bundle Enrolled',
                'count' => $enrollments,
                'drop_off_pct' => 0.0,
                'conversion_pct' => 100.0,
                'drop_off_from_previous' => $paymentSuccess > 0 ? round((($paymentSuccess - $enrollments) / $paymentSuccess) * 100, 1) : 0.0,
                'conversion_from_previous' => $paymentSuccess > 0 ? round(($enrollments / $paymentSuccess) * 100, 1) : 0.0,
            ],
        ];

        $overallRate = $views > 0 ? round(($enrollments / $views) * 100, 2) : 0.0;

        return [
            'views' => $views,
            'cta_clicks' => $ctaClicks,
            'leads' => $leads,
            'checkout_starts' => $checkoutStarts,
            'payment_success' => $paymentSuccess,
            'enrollments' => $enrollments,
            'overall_conversion_rate' => $overallRate,
            'stages' => $stages,
            'steps' => $stages,
        ];
    }
}
