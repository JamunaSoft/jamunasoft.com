<?php

namespace App\Console\Commands;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\Solution;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate public/sitemap.xml for all public pages';

    public function handle(): int
    {
        $sitemap = Sitemap::create();

        $staticRoutes = [
            ['home', 1.0, Url::CHANGE_FREQUENCY_WEEKLY],
            ['services.index', 0.9, Url::CHANGE_FREQUENCY_WEEKLY],
            ['solutions.index', 0.8, Url::CHANGE_FREQUENCY_WEEKLY],
            ['portfolio.index', 0.8, Url::CHANGE_FREQUENCY_WEEKLY],
            ['hosting', 0.8, Url::CHANGE_FREQUENCY_WEEKLY],
            ['packages.index', 0.8, Url::CHANGE_FREQUENCY_WEEKLY],
            ['about', 0.7, Url::CHANGE_FREQUENCY_MONTHLY],
            ['blog.index', 0.7, Url::CHANGE_FREQUENCY_DAILY],
            ['contact.form', 0.7, Url::CHANGE_FREQUENCY_MONTHLY],
            ['quote.create', 0.9, Url::CHANGE_FREQUENCY_MONTHLY],
        ];

        foreach ($staticRoutes as [$name, $priority, $frequency]) {
            $sitemap->add(
                Url::create(route($name))
                    ->setPriority($priority)
                    ->setChangeFrequency($frequency),
            );
        }

        Service::query()->active()->where('seo_noindex', false)->get()
            ->each(fn (Service $service) => $sitemap->add(
                Url::create(route('services.show', $service))
                    ->setLastModificationDate($service->updated_at ?? now())
                    ->setPriority(0.8),
            ));

        Solution::query()->active()->where('seo_noindex', false)->get()
            ->each(fn (Solution $solution) => $sitemap->add(
                Url::create(route('solutions.show', $solution))
                    ->setLastModificationDate($solution->updated_at ?? now())
                    ->setPriority(0.7),
            ));

        Portfolio::query()->active()->where('seo_noindex', false)->get()
            ->each(fn (Portfolio $portfolio) => $sitemap->add(
                Url::create(route('portfolio.show', $portfolio))
                    ->setLastModificationDate($portfolio->updated_at ?? now())
                    ->setPriority(0.6),
            ));

        Page::query()->published()->where('seo_noindex', false)->get()
            ->each(fn (Page $page) => $sitemap->add(
                Url::create(route('page.show', $page))
                    ->setLastModificationDate($page->updated_at ?? now())
                    ->setPriority(0.5),
            ));

        BlogPost::query()->published()->where('seo_noindex', false)->get()
            ->each(fn (BlogPost $post) => $sitemap->add(
                Url::create(route('blog.show', $post))
                    ->setLastModificationDate($post->updated_at ?? $post->published_at ?? now())
                    ->setPriority(0.6),
            ));

        BlogCategory::query()->active()->get()
            ->each(fn (BlogCategory $category) => $sitemap->add(
                Url::create(route('blog.category', $category))
                    ->setPriority(0.5),
            ));

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->refreshRobotsSitemapLine();

        $this->info('Sitemap written to public/sitemap.xml');

        return self::SUCCESS;
    }

    /**
     * Keep the absolute Sitemap URL in robots.txt in sync with APP_URL.
     */
    protected function refreshRobotsSitemapLine(): void
    {
        $robotsPath = public_path('robots.txt');

        if (! is_file($robotsPath)) {
            return;
        }

        $contents = (string) file_get_contents($robotsPath);
        $line = 'Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml';

        $contents = preg_match('/^Sitemap:.*$/m', $contents)
            ? preg_replace('/^Sitemap:.*$/m', $line, $contents)
            : rtrim($contents).PHP_EOL.PHP_EOL.$line.PHP_EOL;

        file_put_contents($robotsPath, $contents);
    }
}
