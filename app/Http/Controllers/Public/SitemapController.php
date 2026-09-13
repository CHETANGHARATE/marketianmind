<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Bundle;
use App\Models\Course;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    /**
     * Generate and return a dynamic XML sitemap.
     */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap_xml', 3600, function () {
            $urls = [];

            // 1. Core Static & Directory Pages
            $urls[] = [
                'loc' => url('/'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ];

            if (Route::has('courses.index')) {
                $urls[] = [
                    'loc' => route('courses.index'),
                    'lastmod' => now()->toIso8601String(),
                    'changefreq' => 'daily',
                    'priority' => '0.9',
                ];
            }

            if (Route::has('bundles.index')) {
                $urls[] = [
                    'loc' => route('bundles.index'),
                    'lastmod' => now()->toIso8601String(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }

            if (Route::has('blog.index')) {
                $urls[] = [
                    'loc' => route('blog.index'),
                    'lastmod' => now()->toIso8601String(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ];
            }

            if (Route::has('about')) {
                $urls[] = [
                    'loc' => route('about'),
                    'lastmod' => now()->toIso8601String(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ];
            }

            if (Route::has('contact')) {
                $urls[] = [
                    'loc' => route('contact'),
                    'lastmod' => now()->toIso8601String(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ];
            }

            // 2. Published Courses
            $courses = Course::published()->select(['slug', 'updated_at'])->get();
            foreach ($courses as $course) {
                $urls[] = [
                    'loc' => route('courses.show', $course->slug),
                    'lastmod' => $course->updated_at->toIso8601String(),
                    'changefreq' => 'weekly',
                    'priority' => '0.9',
                ];
            }

            // 3. Published Bundles
            if (\Illuminate\Support\Facades\Schema::hasTable('bundles')) {
                $bundles = Bundle::where('status', 'published')->select(['slug', 'updated_at'])->get();
                foreach ($bundles as $bundle) {
                    $urls[] = [
                        'loc' => route('bundles.show', $bundle->slug),
                        'lastmod' => $bundle->updated_at->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            }

            // 4. Published Articles
            $articles = Article::published()->select(['slug', 'published_at', 'updated_at'])->get();
            foreach ($articles as $article) {
                $urls[] = [
                    'loc' => route('blog.show', $article->slug),
                    'lastmod' => ($article->published_at ?? $article->updated_at)->toIso8601String(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }

            // Construct XML output
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xmlString .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($urls as $item) {
                $xmlString .= "  <url>\n";
                $xmlString .= "    <loc>" . htmlspecialchars($item['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
                $xmlString .= "    <lastmod>{$item['lastmod']}</lastmod>\n";
                $xmlString .= "    <changefreq>{$item['changefreq']}</changefreq>\n";
                $xmlString .= "    <priority>{$item['priority']}</priority>\n";
                $xmlString .= "  </url>\n";
            }

            $xmlString .= '</urlset>';

            return $xmlString;
        });

        return response($xml, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }
}
