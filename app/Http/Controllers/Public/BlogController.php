<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use App\Models\Course;
use App\Models\SlugRedirect;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(protected SeoService $seoService)
    {
    }

    /**
     * Display a listing of published educational articles and tutorials.
     */
    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');
        $tagSlug = $request->query('tag');
        $search = $request->query('q');

        $query = Article::published()
            ->with(['category', 'author', 'tags'])
            ->latest('published_at')
            ->latest('id');

        if ($search) {
            $query->search($search);
        }

        if ($categorySlug) {
            $query->byCategory($categorySlug);
        }

        if ($tagSlug) {
            $query->byTag($tagSlug);
        }

        $articles = $query->paginate(9)->withQueryString();

        // Featured hero article only on page 1 without active filters
        $featuredArticle = null;
        if (!$search && !$categorySlug && !$tagSlug && $request->query('page', 1) == 1) {
            $featuredArticle = Article::published()
                ->featured()
                ->with(['category', 'author'])
                ->latest('published_at')
                ->first();
        }

        $categories = ArticleCategory::active()
            ->withCount(['articles' => fn($q) => $q->published()])
            ->orderBy('name')
            ->get();

        $popularTags = ArticleTag::withCount(['articles' => fn($q) => $q->published()])
            ->has('articles', '>', 0)
            ->orderByDesc('articles_count')
            ->take(12)
            ->get();

        // Build dynamic SEO metadata
        $title = 'Marketing Insights, Strategies & Guides';
        if ($categorySlug) {
            $cat = $categories->firstWhere('slug', $categorySlug);
            if ($cat) {
                $title = "{$cat->name} Articles & Guides";
            }
        } elseif ($tagSlug) {
            $tag = $popularTags->firstWhere('slug', $tagSlug);
            if ($tag) {
                $title = "Topic: #{$tag->name}";
            }
        } elseif ($search) {
            $title = "Search results for: {$search}";
        }

        $breadcrumbs = [
            'Home' => url('/'),
            'Blog' => route('blog.index'),
        ];
        if ($categorySlug && isset($cat)) {
            $breadcrumbs[$cat->name] = route('blog.index', ['category' => $categorySlug]);
        }

        $seo = $this->seoService->buildMeta([
            'title' => $title,
            'description' => 'Explore actionable digital marketing articles, lead generation tutorials, and growth playbooks by Marketian Mind.',
            'canonical' => route('blog.index', array_filter(['category' => $categorySlug, 'tag' => $tagSlug])),
            'schemas' => [
                $this->seoService->buildOrganizationSchema(),
                $this->seoService->buildBreadcrumbSchema($breadcrumbs),
            ],
        ]);

        return view('public.blog.index', compact(
            'articles',
            'featuredArticle',
            'categories',
            'popularTags',
            'categorySlug',
            'tagSlug',
            'search',
            'seo'
        ));
    }

    /**
     * Display a single published article with social sharing, author bio, and course CTAs.
     */
    public function show(Request $request, string $slug)
    {
        $article = Article::where('slug', $slug)
            ->with(['author', 'category', 'tags', 'seo'])
            ->first();

        // If not found, check 301 slug redirects table
        if (!$article) {
            $redirect = SlugRedirect::where('old_path', $slug)
                ->orWhere('old_path', "/blog/{$slug}")
                ->orWhere('old_path', "blog/{$slug}")
                ->first();

            if ($redirect) {
                $redirect->recordHit();
                return redirect($redirect->new_path, $redirect->status_code);
            }

            abort(404);
        }

        // Enforce published status; allow draft preview only for logged-in admin
        if (!$article->isPublished()) {
            $user = auth()->user();
            if (!$user || !method_exists($user, 'isAdmin') || !$user->isAdmin()) {
                abort(404);
            }
        }

        // Related articles from same category or fallback to latest
        $relatedArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->when($article->category_id, fn($q) => $q->where('category_id', $article->category_id))
            ->with(['category', 'author'])
            ->latest('published_at')
            ->take(3)
            ->get();

        if ($relatedArticles->count() < 3) {
            $needed = 3 - $relatedArticles->count();
            $fillers = Article::published()
                ->where('id', '!=', $article->id)
                ->whereNotIn('id', $relatedArticles->pluck('id'))
                ->with(['category', 'author'])
                ->latest('published_at')
                ->take($needed)
                ->get();
            $relatedArticles = $relatedArticles->merge($fillers);
        }

        // Internal course discovery (recommended courses)
        $recommendedCourses = Course::published()
            ->with('category')
            ->latest('id')
            ->take(3)
            ->get();

        // Assemble SEO metadata
        $seoMeta = $article->seo;
        $title = $seoMeta?->meta_title ?: $article->title;
        $description = $seoMeta?->meta_description ?: ($article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content), 160));
        $canonical = $seoMeta?->canonical_url ?: route('blog.show', $article->slug);
        $robots = $seoMeta?->robots ?: ($article->isPublished() ? 'index, follow' : 'noindex, nofollow');
        $ogImage = $seoMeta?->og_image ?: ($article->featured_image ? url($article->featured_image) : null);

        $breadcrumbs = [
            'Home' => url('/'),
            'Blog' => route('blog.index'),
        ];
        if ($article->category) {
            $breadcrumbs[$article->category->name] = route('blog.index', ['category' => $article->category->slug]);
        }
        $breadcrumbs[$article->title] = route('blog.show', $article->slug);

        $seo = $this->seoService->buildMeta([
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_title' => $seoMeta?->og_title ?: $title,
            'og_description' => $seoMeta?->og_description ?: $description,
            'og_image' => $ogImage,
            'og_type' => 'article',
            'twitter_card' => $seoMeta?->twitter_card ?: 'summary_large_image',
            'schemas' => [
                $this->seoService->buildArticleSchema($article),
                $this->seoService->buildBreadcrumbSchema($breadcrumbs),
            ],
        ]);

        return view('public.blog.show', compact(
            'article',
            'relatedArticles',
            'recommendedCourses',
            'breadcrumbs',
            'seo'
        ));
    }
}
