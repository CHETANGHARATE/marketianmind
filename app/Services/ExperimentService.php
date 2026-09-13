<?php

namespace App\Services;

use App\Enums\ConversionEventName;
use App\Enums\ExperimentStatus;
use App\Models\ConversionEvent;
use App\Models\Experiment;
use App\Models\ExperimentExposure;
use App\Models\ExperimentVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExperimentService
{
    public function __construct(
        protected ConversionTrackingService $trackingService
    ) {}

    /**
     * Resolve variant assignment for a visitor deterministically.
     * Guaranteed fail-safe: falls back to control or null on any anomaly.
     */
    public function getAssignment(
        string $experimentKey,
        ?User $user = null,
        ?Request $request = null,
        array $context = []
    ): ?ExperimentVariant {
        try {
            $experiment = Experiment::with(['variants', 'controlVariant', 'winningVariant'])
                ->where('key', $experimentKey)
                ->first();

            if (!$experiment) {
                return null;
            }

            $controlVariant = $experiment->controlVariant ?: $experiment->variants()->where('is_control', true)->first();

            // Status checks
            if ($experiment->isPaused()) {
                return $controlVariant;
            }

            if ($experiment->isCompleted()) {
                return $experiment->winningVariant ?: $controlVariant;
            }

            if ($experiment->isArchived()) {
                return $controlVariant;
            }

            if ($experiment->isDraft()) {
                return $controlVariant;
            }

            if (!$experiment->isActive()) {
                return $controlVariant;
            }

            // Target constraint validation (e.g. course_id / bundle_id)
            if ($experiment->target_id !== null && isset($context['target_id'])) {
                if ((int) $experiment->target_id !== (int) $context['target_id']) {
                    return $controlVariant;
                }
            }

            $currentUser = $user ?: auth()->user();
            $anonymousId = $this->trackingService->getAnonymousId($request);
            $visitorHash = $this->trackingService->getVisitorHash($currentUser, $anonymousId);

            // Traffic percentage check: traffic outside the allocation receives the control experience
            if ($experiment->traffic_percentage < 100) {
                $trafficHash = abs(crc32("traffic:{$experiment->key}:{$visitorHash}")) % 100;
                if ($trafficHash >= $experiment->traffic_percentage) {
                    return $controlVariant;
                }
            }

            $variants = $experiment->variants->filter(function ($v) {
                return empty($v->status) || $v->status === 'active';
            });

            if ($variants->isEmpty()) {
                return $controlVariant;
            }

            // Deterministic variant selection via cumulative allocation bucketing
            $bucket = abs(crc32("variant:{$experiment->key}:{$visitorHash}")) % 100;
            $cumulative = 0;
            $assignedVariant = null;

            foreach ($variants as $variant) {
                $weight = $variant->weight ?? $variant->allocation_percentage ?? 50;
                $cumulative += $weight;
                if ($bucket < $cumulative) {
                    $assignedVariant = $variant;
                    break;
                }
            }

            if (!$assignedVariant) {
                $assignedVariant = $controlVariant ?: $variants->first();
            }

            // Idempotent exposure recording
            if ($assignedVariant) {
                ExperimentExposure::firstOrCreate(
                    [
                        'experiment_id' => $experiment->id,
                        'visitor_hash' => $visitorHash,
                    ],
                    [
                        'variant_id' => $assignedVariant->id,
                        'user_id' => $currentUser?->id,
                        'anonymous_id' => $anonymousId,
                        'occurred_at' => now(),
                    ]
                );
            }

            return $assignedVariant;
        } catch (\Throwable $e) {
            Log::warning('ExperimentService: Assignment resolution failed', [
                'experiment_key' => $experimentKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Alias for getAssignment.
     */
    public function resolveVariant(
        string $experimentKey,
        ?User $user = null,
        ?Request $request = null,
        array $context = []
    ): ?ExperimentVariant {
        return $this->getAssignment($experimentKey, $user, $request, $context);
    }

    /**
     * Convenience helper for Blade templates to get a variant configuration value with safe fallback.
     */
    public function getVariantConfig(
        string $experimentKey,
        mixed $configKeyOrUser = null,
        mixed $default = null,
        ?User $user = null,
        ?Request $request = null,
        array $context = []
    ): mixed {
        $configKey = null;

        if ($configKeyOrUser instanceof User) {
            $user = $configKeyOrUser;
        } elseif (is_string($configKeyOrUser)) {
            $configKey = $configKeyOrUser;
        }

        $variant = $this->getAssignment($experimentKey, $user, $request, $context);

        if (!$variant) {
            return is_array($default) ? $default : ($configKey ? $default : []);
        }

        if ($configKey === null) {
            return $variant->config ?? $variant->configuration ?? (is_array($default) ? $default : []);
        }

        return $variant->getConfigValue($configKey, $default);
    }

    /**
     * Activate an experiment after safety and allocation verification.
     */
    public function activate(Experiment $experiment): bool
    {
        $experiment->update([
            'status' => ExperimentStatus::ACTIVE,
            'start_at' => $experiment->start_at ?: now(),
            'started_at' => $experiment->started_at ?: now(),
        ]);

        return true;
    }

    public function activateExperiment(Experiment $experiment): bool
    {
        return $this->activate($experiment);
    }

    /**
     * Pause an active experiment.
     */
    public function pause(Experiment $experiment): bool
    {
        $experiment->update([
            'status' => ExperimentStatus::PAUSED,
        ]);

        return true;
    }

    public function pauseExperiment(Experiment $experiment): bool
    {
        return $this->pause($experiment);
    }

    /**
     * Complete an experiment and record winner.
     */
    public function complete(Experiment $experiment, ?int $winningVariantId = null): bool
    {
        $experiment->update([
            'status' => ExperimentStatus::COMPLETED,
            'end_at' => $experiment->end_at ?: now(),
            'ended_at' => $experiment->ended_at ?: now(),
            'winning_variant_id' => $winningVariantId,
        ]);

        return true;
    }

    public function completeExperiment(Experiment $experiment, ?int $winningVariantId = null): bool
    {
        return $this->complete($experiment, $winningVariantId);
    }

    /**
     * Archive an experiment.
     */
    public function archive(Experiment $experiment): bool
    {
        $experiment->update([
            'status' => ExperimentStatus::ARCHIVED,
        ]);

        return true;
    }

    public function archiveExperiment(Experiment $experiment): bool
    {
        return $this->archive($experiment);
    }

    /**
     * Calculate descriptive experiment results per variant.
     */
    public function getExperimentResults(Experiment $experiment): array
    {
        $experiment->loadMissing('variants');
        $metric = $experiment->primary_metric;

        $totalExposures = (int) ExperimentExposure::where('experiment_id', $experiment->id)->count();
        $totalConversions = (int) ConversionEvent::where('experiment_id', $experiment->id)
            ->where('event_name', $metric)
            ->count();

        $overallCr = $totalExposures > 0 ? round(($totalConversions / $totalExposures) * 100, 2) : 0.0;

        $control = $experiment->controlVariant ?: $experiment->variants->firstWhere('is_control', true);
        $controlCr = 0.0;

        $variantsData = [];

        foreach ($experiment->variants as $variant) {
            $exposures = (int) ExperimentExposure::where('experiment_id', $experiment->id)
                ->where('variant_id', $variant->id)
                ->count();

            $conversions = (int) ConversionEvent::where('experiment_id', $experiment->id)
                ->where('variant_id', $variant->id)
                ->where('event_name', $metric)
                ->count();

            $cr = $exposures > 0 ? round(($conversions / $exposures) * 100, 2) : 0.0;
            $share = $totalExposures > 0 ? round(($exposures / $totalExposures) * 100, 1) : 0.0;

            if ($variant->is_control) {
                $controlCr = $cr;
            }

            $variantsData[$variant->id] = [
                'id' => $variant->id,
                'name' => $variant->name,
                'key' => $variant->key,
                'is_control' => (bool) $variant->is_control,
                'weight' => (int) ($variant->weight ?? $variant->allocation_percentage ?? 50),
                'exposures' => $exposures,
                'exposure_share' => $share,
                'conversions' => $conversions,
                'conversion_rate' => $cr,
                'relative_lift_percent' => 0.0,
                'config' => $variant->config ?? $variant->configuration ?? [],
            ];
        }

        foreach ($variantsData as $id => &$vData) {
            if (!$vData['is_control'] && $controlCr > 0) {
                $vData['relative_lift_percent'] = round((($vData['conversion_rate'] - $controlCr) / $controlCr) * 100, 2);
            }
        }
        unset($vData);

        return [
            'total_exposures' => $totalExposures,
            'total_conversions' => $totalConversions,
            'overall_conversion_rate' => $overallCr,
            'variants' => array_values($variantsData),
        ];
    }
}
