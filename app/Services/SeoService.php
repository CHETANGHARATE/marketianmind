<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Course;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Generate a canonical URL, stripping query parameters except specifically allowed ones.
     */
    public function generateCanonical(?string $url = null, array $keepParams = []): string
    {
        $targetUrl = $url ?: url()->current();

        $parts = parse_url($targetUrl);
        if (!$parts || !isset($parts['host'])) {
            return $targetUrl;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) && !in_array($parts['port'], [80, 443]) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '/';

        $cleanUrl = "{$scheme}://{$host}{$port}{$path}";

        if (!empty($parts['query']) && !empty($keepParams)) {
            parse_str($parts['query'], $queryParams);
            $filteredParams = array_intersect_key($queryParams, array_flip($keepParams));
            if (!empty($filteredParams)) {
                $cleanUrl .= '?' . http_build_query($filteredParams);
            }
        }

        return rtrim($cleanUrl, '/');
    }

    /**
     * Build standard SEO metadata array.
     */
    public function buildMeta(array $custom = []): array
    {
        $siteName = config('app.name', 'Marketian Mind');
        $defaultDesc = 'Marketian Mind — Learn practical digital marketing for business owners, startup founders, and entrepreneurs.';
        $defaultImage = asset('images/og-default.jpg');

        $title = !empty($custom['title']) ? $custom['title'] : $siteName;
        if (!Str::contains($title, $siteName)) {
            $title = "{$title} | {$siteName}";
        }

        $description = !empty($custom['description'])
            ? Str::limit(strip_tags($custom['description']), 160)
            : $defaultDesc;

        $canonical = !empty($custom['canonical'])
            ? $this->generateCanonical($custom['canonical'], $custom['keep_params'] ?? [])
            : $this->generateCanonical(null, $custom['keep_params'] ?? []);

        $robots = !empty($custom['robots']) ? $custom['robots'] : 'index, follow';

        // OG & Twitter tags
        $ogTitle = $custom['og_title'] ?? $title;
        $ogDescription = $custom['og_description'] ?? $description;
        $ogImage = $custom['og_image'] ?? $defaultImage;
        $ogType = $custom['og_type'] ?? 'website';
        $ogUrl = $custom['og_url'] ?? $canonical;

        $twitterCard = $custom['twitter_card'] ?? 'summary_large_image';
        $twitterTitle = $custom['twitter_title'] ?? $ogTitle;
        $twitterDescription = $custom['twitter_description'] ?? $ogDescription;
        $twitterImage = $custom['twitter_image'] ?? $ogImage;

        // Structured data schemas
        $schemas = $custom['schemas'] ?? [];

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
            'og_image' => $ogImage,
            'og_type' => $ogType,
            'og_url' => $ogUrl,
            'og_site_name' => $siteName,
            'twitter_card' => $twitterCard,
            'twitter_title' => $twitterTitle,
            'twitter_description' => $twitterDescription,
            'twitter_image' => $twitterImage,
            'schemas' => $schemas,
        ];
    }

    /**
     * Build Schema.org Organization structured data.
     */
    public function buildOrganizationSchema(): array
    {
        $url = rtrim(config('app.url', url('/')), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Marketian Mind',
            'url' => $url,
            'logo' => "{$url}/images/logo.png",
            'description' => 'Online Marketing Education Platform for small business owners, startup founders, and entrepreneurs.',
            'sameAs' => [
                'https://twitter.com/marketianmind',
                'https://www.linkedin.com/company/marketianmind',
                'https://www.youtube.com/@marketianmind',
            ],
        ];
    }

    /**
     * Build Schema.org WebSite structured data with SearchAction.
     */
    public function buildWebSiteSchema(): array
    {
        $url = rtrim(config('app.url', url('/')), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Marketian Mind',
            'url' => $url,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => "{$url}/courses?q={search_term_string}",
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Build Schema.org Course structured data.
     */
    public function buildCourseSchema(Course $course): array
    {
        $url = route('courses.show', $course->slug);
        $price = $course->discount_price !== null ? (float) $course->discount_price : (float) $course->price;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course->title,
            'description' => $course->short_description ?: Str::limit(strip_tags($course->description ?? ''), 200),
            'url' => $url,
            'provider' => [
                '@type' => 'Organization',
                'name' => 'Marketian Mind',
                'sameAs' => rtrim(config('app.url', url('/')), '/'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $course->is_free ? '0.00' : number_format($price, 2, '.', ''),
                'priceCurrency' => 'INR',
                'availability' => 'https://schema.org/InStock',
                'url' => $url,
            ],
        ];

        if (!empty($course->thumbnail)) {
            $schema['image'] = Str::startsWith($course->thumbnail, ['http://', 'https://'])
                ? $course->thumbnail
                : url($course->thumbnail);
        }

        if (!empty($course->instructorDisplayName())) {
            $schema['instructor'] = [
                '@type' => 'Person',
                'name' => $course->instructorDisplayName(),
            ];
        }

        return $schema;
    }

    /**
     * Build Schema.org Article structured data.
     */
    public function buildArticleSchema(Article $article): array
    {
        $url = route('blog.show', $article->slug);
        $siteUrl = rtrim(config('app.url', url('/')), '/');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->excerpt ?: Str::limit(strip_tags($article->content), 160),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $article->author?->name ?? 'Marketian Mind Editorial Team',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Marketian Mind',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => "{$siteUrl}/images/logo.png",
                ],
            ],
            'datePublished' => ($article->published_at ?? $article->created_at)->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
        ];

        if (!empty($article->featured_image)) {
            $schema['image'] = Str::startsWith($article->featured_image, ['http://', 'https://'])
                ? $article->featured_image
                : url($article->featured_image);
        }

        return $schema;
    }

    /**
     * Build Schema.org BreadcrumbList structured data.
     */
    public function buildBreadcrumbSchema(array $items): array
    {
        $elements = [];
        $position = 1;

        foreach ($items as $name => $url) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => $url,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}
