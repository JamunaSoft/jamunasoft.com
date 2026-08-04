<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Contracts\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $featured = null;

        if (! request()->has('page')) {
            $featured = BlogPost::query()
                ->published()
                ->featured()
                ->latest('published_at')
                ->with(['media', 'category', 'author'])
                ->first();
        }

        $posts = BlogPost::query()
            ->published()
            ->when($featured, fn ($query) => $query->whereKeyNot($featured->id))
            ->latest('published_at')
            ->with(['media', 'category', 'author'])
            ->paginate(9)
            ->withQueryString();

        return view('blog.index', [
            'featured' => $featured,
            'posts' => $posts,
            ...$this->sidebar(),
            'seo' => [
                'title' => __('Blog & Insights'),
                'description' => __('Guides, tips and insights on software, websites, hosting and digital marketing from the Jamuna Soft team.'),
            ],
        ]);
    }

    public function category(string $slug): View
    {
        $category = BlogCategory::query()->active()->where('slug', $slug)->firstOrFail();

        $posts = $category->posts()
            ->published()
            ->latest('published_at')
            ->with(['media', 'category', 'author'])
            ->paginate(9)
            ->withQueryString();

        return view('blog.index', [
            'featured' => null,
            'posts' => $posts,
            'listTitle' => $category->t('name'),
            'listDescription' => $category->t('description'),
            ...$this->sidebar(),
            'seo' => [
                'title' => $category->t('name').' — '.__('Blog'),
                'description' => str(strip_tags((string) $category->t('description')))->limit(160)->toString() ?: null,
            ],
        ]);
    }

    public function tag(string $slug): View
    {
        $tag = BlogTag::query()->where('slug', $slug)->firstOrFail();

        $posts = $tag->posts()
            ->published()
            ->latest('published_at')
            ->with(['media', 'category', 'author'])
            ->paginate(9)
            ->withQueryString();

        return view('blog.index', [
            'featured' => null,
            'posts' => $posts,
            'listTitle' => '#'.$tag->name,
            'listDescription' => null,
            ...$this->sidebar(),
            'seo' => [
                'title' => $tag->name.' — '.__('Blog'),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->published()
            ->where('slug', $slug)
            ->with(['media', 'category', 'author', 'tags'])
            ->firstOrFail();

        $post->increment('views');

        $related = BlogPost::query()
            ->published()
            ->whereKeyNot($post->id)
            ->when(
                $post->blog_category_id,
                fn ($query) => $query->where('blog_category_id', $post->blog_category_id),
            )
            ->latest('published_at')
            ->with(['media', 'category', 'author'])
            ->take(3)
            ->get();

        return view('blog.show', [
            'post' => $post,
            'related' => $related,
            'seo' => [
                'title' => $post->t('seo_title') ?: $post->t('title'),
                'description' => $post->t('seo_description') ?: str(strip_tags((string) ($post->t('excerpt') ?: $post->t('content'))))->limit(160)->toString(),
                'image' => $post->getFirstMediaUrl('featured', 'card') ?: null,
                'type' => 'article',
                'noindex' => (bool) $post->seo_noindex,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected function sidebar(): array
    {
        return [
            'blogCategories' => BlogCategory::query()
                ->active()
                ->ordered()
                ->withCount(['posts' => fn ($query) => $query->published()])
                ->get(),
            'recentPosts' => BlogPost::query()
                ->published()
                ->latest('published_at')
                ->take(5)
                ->get(),
        ];
    }
}
