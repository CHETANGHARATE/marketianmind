<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    /**
     * Display the About page.
     */
    public function index(SeoService $seoService): View
    {
        $seo = $seoService->buildMeta([
            'title' => 'About Us — Practical Marketing Education for Entrepreneurs',
            'description' => 'Marketian Mind empowers small business owners, startup founders, and local operators with actionable, ROI-focused marketing education.',
            'canonical' => route('about'),
            'schemas' => [
                $seoService->buildOrganizationSchema(),
                $seoService->buildBreadcrumbSchema([
                    'Home' => url('/'),
                    'About' => route('about'),
                ]),
            ],
        ]);

        return view('public.about', compact('seo'));
    }
}
