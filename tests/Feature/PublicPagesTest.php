<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\Solution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_static_pages_load(): void
    {
        foreach (['/', '/services', '/solutions', '/portfolio', '/hosting', '/packages', '/about', '/blog', '/contact', '/request-a-quotation'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_pages_load_with_seeded_demo_content(): void
    {
        $this->seed();

        foreach (['/', '/services', '/services/website-development', '/solutions/education', '/portfolio', '/packages', '/blog', '/page/privacy-policy'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_service_detail_page_renders_with_seo_tags(): void
    {
        $service = Service::create([
            'name' => 'Test Service',
            'slug' => 'test-service',
            'excerpt' => 'A test service excerpt.',
            'seo_title' => 'Custom SEO Title',
            'seo_description' => 'Custom SEO description.',
            'is_active' => true,
        ]);

        $this->get('/services/test-service')
            ->assertOk()
            ->assertSee('<title>Custom SEO Title', false)
            ->assertSee('Custom SEO description.', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('og:title', false);
    }

    public function test_inactive_content_is_not_visible(): void
    {
        Service::create(['name' => 'Hidden', 'slug' => 'hidden-service', 'is_active' => false]);
        Solution::create(['name' => 'Hidden', 'slug' => 'hidden-solution', 'is_active' => false]);
        Portfolio::create(['title' => 'Hidden', 'slug' => 'hidden-project', 'is_active' => false]);

        $this->get('/services/hidden-service')->assertNotFound();
        $this->get('/solutions/hidden-solution')->assertNotFound();
        $this->get('/portfolio/hidden-project')->assertNotFound();
    }

    public function test_draft_and_scheduled_blog_posts_are_hidden(): void
    {
        BlogPost::create([
            'title' => 'Draft Post', 'slug' => 'draft-post',
            'content' => 'x', 'status' => PublishStatus::Draft, 'published_at' => now()->subDay(),
        ]);
        BlogPost::create([
            'title' => 'Scheduled Post', 'slug' => 'scheduled-post',
            'content' => 'x', 'status' => PublishStatus::Published, 'published_at' => now()->addWeek(),
        ]);
        $published = BlogPost::create([
            'title' => 'Live Post', 'slug' => 'live-post',
            'content' => 'Hello world content.', 'status' => PublishStatus::Published, 'published_at' => now()->subHour(),
        ]);

        $this->get('/blog/draft-post')->assertNotFound();
        $this->get('/blog/scheduled-post')->assertNotFound();
        $this->get('/blog/live-post')->assertOk()->assertSee('Live Post');

        $this->get('/blog')->assertSee('Live Post')->assertDontSee('Draft Post');
    }

    public function test_blog_post_view_count_increments(): void
    {
        $post = BlogPost::create([
            'title' => 'Counted', 'slug' => 'counted',
            'content' => 'x', 'status' => PublishStatus::Published, 'published_at' => now()->subHour(),
        ]);

        $this->get('/blog/counted')->assertOk();

        $this->assertSame(1, $post->fresh()->views);
    }

    public function test_draft_pages_are_hidden_and_published_pages_render(): void
    {
        Page::create(['title' => 'Secret', 'slug' => 'secret', 'status' => PublishStatus::Draft]);
        Page::create(['title' => 'Public Page', 'slug' => 'public-page', 'status' => PublishStatus::Published, 'content' => '<p>Visible</p>']);

        $this->get('/page/secret')->assertNotFound();
        $this->get('/page/public-page')->assertOk()->assertSee('Visible');
    }

    public function test_redirect_manager_handles_missing_pages(): void
    {
        Redirect::create(['from_path' => '/old-page', 'to_path' => '/services', 'status_code' => 301]);

        $this->get('/old-page')->assertRedirect('/services');
        $this->assertSame(1, Redirect::first()->fresh()->hits);
    }

    public function test_inactive_redirects_fall_through_to_404(): void
    {
        Redirect::create(['from_path' => '/dead-link', 'to_path' => '/services', 'is_active' => false]);

        $this->get('/dead-link')->assertNotFound();
    }

    public function test_404_page_is_branded(): void
    {
        $this->get('/totally-missing')->assertNotFound()->assertSee('404');
    }

    public function test_locale_switcher_switches_language(): void
    {
        $this->get('/locale/bn')->assertRedirect();
        $this->assertSame('bn', session('locale'));

        $this->get('/locale/xx')->assertNotFound();
    }

    public function test_sitemap_generation(): void
    {
        Service::create(['name' => 'Mapped', 'slug' => 'mapped-service', 'is_active' => true]);

        $this->artisan('sitemap:generate')->assertSuccessful();

        $this->assertFileExists(public_path('sitemap.xml'));
        $this->assertStringContainsString('/services/mapped-service', file_get_contents(public_path('sitemap.xml')));
    }
}
