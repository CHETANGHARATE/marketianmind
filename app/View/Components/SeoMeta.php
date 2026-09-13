<?php

namespace App\View\Components;

use App\Services\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SeoMeta extends Component
{
    public array $meta;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?array $seo = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public ?string $image = null,
    ) {
        $seoService = app(SeoService::class);
        $custom = $this->seo ?? [];

        // Backward compatibility with legacy @section('title') and @section('meta_description')
        $view = app('view');
        if (empty($custom['title']) && empty($this->title) && $view->hasSection('title')) {
            $this->title = (string) $view->yieldContent('title');
        }
        if (empty($custom['description']) && empty($this->description) && $view->hasSection('meta_description')) {
            $this->description = (string) $view->yieldContent('meta_description');
        }

        if ($this->title) {
            $custom['title'] = $this->title;
        }
        if ($this->description) {
            $custom['description'] = $this->description;
        }
        if ($this->canonical) {
            $custom['canonical'] = $this->canonical;
        }
        if ($this->robots) {
            $custom['robots'] = $this->robots;
        }
        if ($this->image) {
            $custom['og_image'] = $this->image;
            $custom['twitter_image'] = $this->image;
        }

        // Automatic noindex protection on private, student, and admin routes
        if ($this->isPrivateRoute()) {
            $custom['robots'] = 'noindex, nofollow';
        }

        $this->meta = $seoService->buildMeta($custom);
    }

    /**
     * Check if current request route is a private/internal route that must not be indexed.
     */
    protected function isPrivateRoute(): bool
    {
        $path = request()->path();

        $privatePrefixes = [
            'admin',
            'admin/',
            'student',
            'student/',
            'checkout',
            'checkout/',
            'cart',
            'login',
            'register',
            'password',
            'password/',
            'webhooks',
            'webhooks/',
            'payment',
            'payment/',
            'ref',
            'ref/',
        ];

        foreach ($privatePrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.seo-meta');
    }
}
