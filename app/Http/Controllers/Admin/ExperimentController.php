<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExperimentStatus;
use App\Http\Controllers\Controller;
use App\Models\Experiment;
use App\Models\ExperimentVariant;
use App\Services\AuditLogger;
use App\Services\ExperimentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExperimentController extends Controller
{
    /**
     * Display a listing of experiments.
     */
    public function index(Request $request): View
    {
        $query = Experiment::with('variants')->withCount(['exposures', 'conversions'])->latest();

        if ($request->filled('status')) {
            $statusVal = $request->status;
            if ($statusVal === 'running') {
                $statusVal = ExperimentStatus::ACTIVE->value;
            }
            $query->where('status', $statusVal);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $experiments = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Experiment::count(),
            'running' => Experiment::where('status', ExperimentStatus::RUNNING->value)->count(),
            'paused' => Experiment::where('status', ExperimentStatus::PAUSED->value)->count(),
            'completed' => Experiment::where('status', ExperimentStatus::COMPLETED->value)->count(),
            'draft' => Experiment::where('status', ExperimentStatus::DRAFT->value)->count(),
        ];

        return view('admin.experiments.index', [
            'experiments' => $experiments,
            'stats' => $stats,
            'statuses' => ExperimentStatus::cases(),
        ]);
    }

    /**
     * Show the form for creating a new experiment.
     */
    public function create(): View
    {
        return view('admin.experiments.create');
    }

    /**
     * Store a newly created experiment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-]+$/', 'unique:experiments,key'],
            'description' => ['nullable', 'string', 'max:1000'],
            'target_audience' => ['nullable', 'string', 'max:100'],
            'traffic_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'primary_metric' => ['required', 'string', 'max:100'],
            'variants' => ['required', 'array', 'min:2'],
            'variants.*.key' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'variants.*.name' => ['required', 'string', 'max:100'],
            'variants.*.weight' => ['required', 'integer', 'min:1', 'max:100'],
            'variants.*.is_control' => ['nullable', 'boolean'],
            'variants.*.config' => ['nullable', 'string'],
        ]);

        $experiment = DB::transaction(function () use ($validated) {
            $experiment = Experiment::create([
                'name' => $validated['name'],
                'key' => $validated['key'],
                'description' => $validated['description'] ?? null,
                'status' => ExperimentStatus::DRAFT,
                'traffic_percentage' => $validated['traffic_percentage'],
                'target_audience' => $validated['target_audience'] ?? 'all',
                'primary_metric' => $validated['primary_metric'],
            ]);

            $hasControl = false;
            foreach ($validated['variants'] as $idx => $v) {
                $isControl = !empty($v['is_control']);
                if ($isControl && !$hasControl) {
                    $hasControl = true;
                } elseif ($idx === 0 && !$hasControl) {
                    $isControl = true;
                    $hasControl = true;
                }

                $config = null;
                if (!empty($v['config'])) {
                    $decoded = json_decode($v['config'], true);
                    $config = is_array($decoded) ? $decoded : ['raw' => $v['config']];
                }

                ExperimentVariant::create([
                    'experiment_id' => $experiment->id,
                    'key' => $v['key'],
                    'name' => $v['name'],
                    'is_control' => $isControl,
                    'weight' => (int) $v['weight'],
                    'config' => $config,
                ]);
            }

            return $experiment;
        });

        AuditLogger::log(
            'created',
            $experiment,
            "Created experiment '{$experiment->name}' ({$experiment->key})",
            null,
            ['key' => $experiment->key]
        );

        return redirect()
            ->route('admin.experiments.show', $experiment)
            ->with('success', "Experiment '{$experiment->name}' created successfully.");
    }

    /**
     * Display the specified experiment with live statistics and results.
     */
    public function show(Experiment $experiment, ExperimentService $experimentService): View
    {
        $experiment->load(['variants', 'winningVariant']);
        $results = $experimentService->getExperimentResults($experiment);

        return view('admin.experiments.show', [
            'experiment' => $experiment,
            'results' => $results,
        ]);
    }

    /**
     * Show the form for editing the specified experiment.
     */
    public function edit(Experiment $experiment): View
    {
        $experiment->load('variants');
        return view('admin.experiments.edit', compact('experiment'));
    }

    /**
     * Update the specified experiment.
     */
    public function update(Request $request, Experiment $experiment): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'target_audience' => ['nullable', 'string', 'max:100'],
            'traffic_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'primary_metric' => ['required', 'string', 'max:100'],
        ]);

        $oldValues = $experiment->only(['name', 'description', 'traffic_percentage', 'target_audience', 'primary_metric']);

        $experiment->update($validated);

        AuditLogger::log(
            'updated',
            $experiment,
            "Updated experiment '{$experiment->name}'",
            [
                'old' => $oldValues,
                'new' => $experiment->only(['name', 'description', 'traffic_percentage', 'target_audience', 'primary_metric']),
            ]
        );

        return redirect()
            ->route('admin.experiments.show', $experiment)
            ->with('success', "Experiment '{$experiment->name}' updated successfully.");
    }

    /**
     * Activate the experiment.
     */
    public function activate(Experiment $experiment, ExperimentService $experimentService): RedirectResponse
    {
        try {
            $experimentService->activateExperiment($experiment);

            AuditLogger::log(
                'activated',
                $experiment,
                "Activated experiment '{$experiment->name}'",
                null,
                ['status' => ExperimentStatus::RUNNING->value]
            );

            return back()->with('success', "Experiment '{$experiment->name}' is now running.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pause the experiment.
     */
    public function pause(Experiment $experiment, ExperimentService $experimentService): RedirectResponse
    {
        try {
            $experimentService->pauseExperiment($experiment);

            AuditLogger::log(
                'paused',
                $experiment,
                "Paused experiment '{$experiment->name}'",
                null,
                ['status' => ExperimentStatus::PAUSED->value]
            );

            return back()->with('success', "Experiment '{$experiment->name}' has been paused.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete the experiment and record winner.
     */
    public function complete(Request $request, Experiment $experiment, ExperimentService $experimentService): RedirectResponse
    {
        $validated = $request->validate([
            'winning_variant_id' => ['nullable', 'integer', Rule::exists('experiment_variants', 'id')->where('experiment_id', $experiment->id)],
        ]);

        try {
            $experimentService->completeExperiment($experiment, $validated['winning_variant_id'] ?? null);

            AuditLogger::log(
                'completed',
                $experiment,
                "Completed experiment '{$experiment->name}'",
                null,
                [
                    'status' => ExperimentStatus::COMPLETED->value,
                    'winning_variant_id' => $validated['winning_variant_id'] ?? null,
                ]
            );

            return back()->with('success', "Experiment '{$experiment->name}' marked as completed.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Archive the experiment.
     */
    public function archive(Experiment $experiment, ExperimentService $experimentService): RedirectResponse
    {
        try {
            $experimentService->archiveExperiment($experiment);

            AuditLogger::log(
                'archived',
                $experiment,
                "Archived experiment '{$experiment->name}'",
                null,
                ['status' => ExperimentStatus::ARCHIVED->value]
            );

            return back()->with('success', "Experiment '{$experiment->name}' archived.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a draft experiment.
     */
    public function destroy(Experiment $experiment): RedirectResponse
    {
        if ($experiment->isRunning()) {
            return back()->with('error', 'Cannot delete an active experiment. Pause or archive it first.');
        }

        $name = $experiment->name;

        AuditLogger::log(
            'deleted',
            $experiment,
            "Deleted experiment '{$name}'"
        );

        $experiment->delete();

        return redirect()
            ->route('admin.experiments.index')
            ->with('success', "Experiment '{$name}' deleted successfully.");
    }
}
