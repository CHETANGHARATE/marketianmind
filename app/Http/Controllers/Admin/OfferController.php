<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OfferDiscountType;
use App\Enums\OfferStatus;
use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Offer;
use App\Models\OfferProduct;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfferController extends Controller
{
    /**
     * Display a listing of promotional offers with status filtering and metrics.
     */
    public function index(Request $request): View
    {
        $query = Offer::query()->withCount(['courses', 'bundles', 'orders']);

        // Search filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('badge_text', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $now = now();
            if ($status === 'active') {
                $query->where('is_active', true)
                    ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                    ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
                    ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit'));
            } elseif ($status === 'scheduled') {
                $query->where('is_active', true)
                    ->where('starts_at', '>', $now);
            } elseif ($status === 'expired') {
                $query->where('is_active', true)
                    ->where(function ($q) use ($now) {
                        $q->where('ends_at', '<', $now)
                            ->orWhere(fn ($sq) => $sq->whereNotNull('usage_limit')->whereColumn('times_used', '>=', 'usage_limit'));
                    });
            } elseif ($status === 'disabled') {
                $query->where('is_active', false);
            }
        }

        $offers = $query->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Metrics
        $totalOffers = Offer::count();
        $activeOffers = Offer::currentlyValid()->count();
        $scheduledOffers = Offer::where('is_active', true)->where('starts_at', '>', now())->count();
        $totalOrdersCount = DB::table('orders')->whereNotNull('offer_id')->count();

        return view('admin.offers.index', compact(
            'offers',
            'totalOffers',
            'activeOffers',
            'scheduledOffers',
            'totalOrdersCount'
        ));
    }

    /**
     * Show the form for creating a new promotional offer.
     */
    public function create(): View
    {
        $courses = Course::published()->orderBy('title')->get();
        $bundles = Bundle::published()->orderBy('title')->get();

        return view('admin.offers.create', compact('courses', 'bundles'));
    }

    /**
     * Store a newly created promotional offer in database.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->has('name') && $request->has('title')) {
            $request->merge(['name' => $request->input('title')]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:offers,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', Rule::enum(OfferDiscountType::class)],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'allow_coupons' => ['nullable', 'boolean'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'badge_text' => ['nullable', 'string', 'max:100'],
            'courses' => ['nullable', 'array'],
            'courses.*' => ['exists:courses,id'],
            'bundles' => ['nullable', 'array'],
            'bundles.*' => ['exists:bundles,id'],
        ]);

        // Percentage validation rule: cannot exceed 100%
        if ($validated['discount_type'] === OfferDiscountType::PERCENTAGE->value && $validated['discount_value'] > 100) {
            return back()->withInput()->withErrors(['discount_value' => 'Percentage discount cannot exceed 100%.']);
        }

        // Ensure at least one product is selected
        $courseIds = $validated['courses'] ?? [];
        $bundleIds = $validated['bundles'] ?? [];

        if (empty($courseIds) && empty($bundleIds)) {
            return back()->withInput()->withErrors(['products' => 'Please select at least one eligible course or bundle for this offer.']);
        }

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Ensure slug uniqueness
        $originalSlug = $slug;
        $counter = 1;
        while (Offer::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        DB::transaction(function () use ($validated, $slug, $courseIds, $bundleIds) {
            $offer = Offer::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'],
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'priority' => $validated['priority'] ?? 0,
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'allow_coupons' => (bool) ($validated['allow_coupons'] ?? false),
                'usage_limit' => $validated['usage_limit'] ?? null,
                'badge_text' => $validated['badge_text'] ?? null,
            ]);

            foreach ($courseIds as $courseId) {
                OfferProduct::create([
                    'offer_id' => $offer->id,
                    'product_type' => 'course',
                    'product_id' => $courseId,
                ]);
            }

            foreach ($bundleIds as $bundleId) {
                OfferProduct::create([
                    'offer_id' => $offer->id,
                    'product_type' => 'bundle',
                    'product_id' => $bundleId,
                ]);
            }

            AuditLogger::log(
                'created',
                $offer,
                "Created promotional offer '{$offer->name}' ({$offer->formattedDiscount()})",
                null,
                [
                    'offer_id' => $offer->id,
                    'name' => $offer->name,
                    'discount_type' => $offer->discount_type->value,
                    'discount_value' => $offer->discount_value,
                    'courses_count' => count($courseIds),
                    'bundles_count' => count($bundleIds),
                ]
            );
        });

        return redirect()
            ->route('admin.offers.index')
            ->with('status', 'Promotional offer created successfully.');
    }

    /**
     * Display the specified offer with details and performance stats.
     */
    public function show(Offer $offer): View
    {
        $offer->load(['courses', 'bundles', 'orders' => fn ($q) => $q->latest()->limit(10)]);

        $totalRevenuePaise = $offer->orders()->where('status', 'paid')->sum('amount');
        $totalDiscountPaise = $offer->orders()->where('status', 'paid')->sum('offer_discount_amount');

        return view('admin.offers.show', compact('offer', 'totalRevenuePaise', 'totalDiscountPaise'));
    }

    /**
     * Show the form for editing the specified offer.
     */
    public function edit(Offer $offer): View
    {
        $courses = Course::published()->orderBy('title')->get();
        $bundles = Bundle::published()->orderBy('title')->get();

        $selectedCourseIds = $offer->courses()->pluck('courses.id')->toArray();
        $selectedBundleIds = $offer->bundles()->pluck('bundles.id')->toArray();

        return view('admin.offers.edit', compact(
            'offer',
            'courses',
            'bundles',
            'selectedCourseIds',
            'selectedBundleIds'
        ));
    }

    /**
     * Update the specified offer in database.
     */
    public function update(Request $request, Offer $offer): RedirectResponse
    {
        if (! $request->has('name') && $request->has('title')) {
            $request->merge(['name' => $request->input('title')]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('offers', 'slug')->ignore($offer->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', Rule::enum(OfferDiscountType::class)],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'allow_coupons' => ['nullable', 'boolean'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'badge_text' => ['nullable', 'string', 'max:100'],
            'courses' => ['nullable', 'array'],
            'courses.*' => ['exists:courses,id'],
            'bundles' => ['nullable', 'array'],
            'bundles.*' => ['exists:bundles,id'],
        ]);

        if ($validated['discount_type'] === OfferDiscountType::PERCENTAGE->value && $validated['discount_value'] > 100) {
            return back()->withInput()->withErrors(['discount_value' => 'Percentage discount cannot exceed 100%.']);
        }

        $courseIds = $validated['courses'] ?? [];
        $bundleIds = $validated['bundles'] ?? [];

        if (empty($courseIds) && empty($bundleIds)) {
            return back()->withInput()->withErrors(['products' => 'Please select at least one eligible course or bundle for this offer.']);
        }

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Check unique slug ignoring current
        if ($slug !== $offer->slug && Offer::where('slug', $slug)->where('id', '!=', $offer->id)->exists()) {
            $slug = "{$slug}-{$offer->id}";
        }

        DB::transaction(function () use ($offer, $validated, $slug, $courseIds, $bundleIds) {
            $offer->update([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'],
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'priority' => $validated['priority'] ?? 0,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'allow_coupons' => (bool) ($validated['allow_coupons'] ?? false),
                'usage_limit' => $validated['usage_limit'] ?? null,
                'badge_text' => $validated['badge_text'] ?? null,
            ]);

            // Sync products
            OfferProduct::where('offer_id', $offer->id)->delete();

            foreach ($courseIds as $courseId) {
                OfferProduct::create([
                    'offer_id' => $offer->id,
                    'product_type' => 'course',
                    'product_id' => $courseId,
                ]);
            }

            foreach ($bundleIds as $bundleId) {
                OfferProduct::create([
                    'offer_id' => $offer->id,
                    'product_type' => 'bundle',
                    'product_id' => $bundleId,
                ]);
            }

            AuditLogger::log(
                'updated',
                $offer,
                "Updated promotional offer '{$offer->name}'",
                null,
                [
                    'offer_id' => $offer->id,
                    'name' => $offer->name,
                    'is_active' => $offer->is_active,
                    'courses_count' => count($courseIds),
                    'bundles_count' => count($bundleIds),
                ]
            );
        });

        return redirect()
            ->route('admin.offers.index')
            ->with('status', 'Promotional offer updated successfully.');
    }

    /**
     * Toggle the active status of an offer.
     */
    public function toggle(Offer $offer): RedirectResponse
    {
        $offer->is_active = ! $offer->is_active;
        $offer->save();

        $action = $offer->is_active ? 'enabled' : 'disabled';

        AuditLogger::log(
            'updated',
            $offer,
            "Offer '{$offer->name}' was {$action}.",
            ['is_active' => ! $offer->is_active],
            ['is_active' => $offer->is_active]
        );

        return back()->with('status', "Offer '{$offer->name}' was {$action} successfully.");
    }

    /**
     * Remove the specified offer from database.
     */
    public function destroy(Offer $offer): RedirectResponse
    {
        $offerName = $offer->name;
        $offerId = $offer->id;

        $offer->delete();

        AuditLogger::log(
            'deleted',
            'Offer',
            "Deleted promotional offer '{$offerName}' (ID: {$offerId})",
            ['offer_id' => $offerId, 'name' => $offerName]
        );

        return redirect()
            ->route('admin.offers.index')
            ->with('status', "Offer '{$offerName}' was deleted successfully.");
    }
}
