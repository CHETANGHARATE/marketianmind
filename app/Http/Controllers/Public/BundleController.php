<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    /**
     * Display a listing of available course bundles and packages.
     */
    public function index(Request $request): View
    {
        $search = trim($request->query('q', ''));

        $query = Bundle::published()
            ->with(['publishedCourses' => function ($q) {
                $q->select('courses.id', 'courses.title', 'courses.slug', 'courses.price', 'courses.discount_price', 'courses.is_free');
            }]);

        if ($search !== '') {
            $query->search($search);
        }

        $bundles = $query->orderByDesc('featured')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('public.bundles.index', compact('bundles', 'search'));
    }

    /**
     * Display detailed breakdown of a specific course bundle.
     */
    public function show(Request $request, Bundle $bundle): View
    {
        if (! $bundle->isPublished()) {
            abort(404, 'The requested course bundle is unavailable or not published.');
        }

        $bundle->load([
            'publishedCourses' => function ($query) {
                $query->with(['category', 'instructor', 'modules.lessons']);
            },
        ]);

        $user = $request->user();
        $isFullyEnrolled = $bundle->hasUserAccess($user);
        $ownedCount = $bundle->userOwnedCoursesCount($user);
        $totalCoursesCount = $bundle->publishedCourses->count();

        $seoService = app(\App\Services\SeoService::class);
        $seoMeta = $bundle->seo;
        $seo = $seoService->buildMeta([
            'title' => $seoMeta?->meta_title ?: $bundle->title,
            'description' => $seoMeta?->meta_description ?: ($bundle->short_description ?: strip_tags($bundle->description ?? '')),
            'canonical' => $seoMeta?->canonical_url ?: route('bundles.show', $bundle->slug),
            'robots' => $seoMeta?->robots ?: 'index, follow',
            'og_title' => $seoMeta?->og_title ?: $bundle->title,
            'og_description' => $seoMeta?->og_description ?: ($bundle->short_description ?: strip_tags($bundle->description ?? '')),
            'og_image' => $seoMeta?->og_image ?: ($bundle->thumbnail ? url($bundle->thumbnail) : null),
            'schemas' => [
                $seoService->buildBreadcrumbSchema([
                    'Home' => url('/'),
                    'Bundles' => route('bundles.index'),
                    $bundle->title => route('bundles.show', $bundle->slug),
                ]),
            ],
        ]);

        // Track Bundle View Conversion Event
        app(\App\Services\ConversionTrackingService::class)->track('bundle_view', [
            'bundle_id' => $bundle->id,
        ]);

        // Resolve Bundle CTA Experiment Assignment
        $experimentService = app(\App\Services\ExperimentService::class);
        $ctaVariant = $experimentService->getAssignment('bundle_cta_text', $user, $request, ['target_id' => $bundle->id])
            ?: $experimentService->getAssignment('bundle_cta', $user, $request, ['target_id' => $bundle->id]);

        return view('public.bundles.show', compact('bundle', 'isFullyEnrolled', 'ownedCount', 'totalCoursesCount', 'seo', 'ctaVariant'));
    }
}
