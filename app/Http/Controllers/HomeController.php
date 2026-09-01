<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Package;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\Solution;
use App\Models\Testimonial;
use App\Models\Tld;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $services = Service::query()->active()->featured()->ordered()->with('media')->take(6)->get();

        if ($services->isEmpty()) {
            $services = Service::query()->active()->ordered()->with('media')->take(6)->get();
        }

        $portfolios = Portfolio::query()->active()->featured()->ordered()->with(['media', 'category'])->take(6)->get();

        if ($portfolios->isEmpty()) {
            $portfolios = Portfolio::query()->active()->ordered()->with(['media', 'category'])->take(6)->get();
        }

        $packages = Package::query()->active()->featured()->ordered()->with('service')->take(3)->get();

        if ($packages->isEmpty()) {
            $packages = Package::query()->active()->ordered()->with('service')->take(3)->get();
        }

        $clientLogos = Portfolio::query()
            ->active()
            ->ordered()
            ->with('media')
            ->get()
            ->filter(fn (Portfolio $portfolio) => $portfolio->hasMedia('client_logo'))
            ->values();

        $testimonials = Testimonial::query()->approved()->featured()->ordered()->with('media')->take(6)->get();

        if ($testimonials->isEmpty()) {
            $testimonials = Testimonial::query()->approved()->ordered()->with('media')->take(6)->get();
        }

        $faqs = Faq::query()->general()->active()->featured()->ordered()->take(6)->get();

        if ($faqs->isEmpty()) {
            $faqs = Faq::query()->general()->active()->ordered()->take(6)->get();
        }

        return view('home', [
            'services' => $services,
            'solutions' => Solution::query()->active()->ordered()->take(8)->get(),
            'portfolios' => $portfolios,
            'packages' => $packages,
            'clientLogos' => $clientLogos,
            'searchTlds' => Tld::query()->active()->ordered()->take(6)->get(),
            'testimonials' => $testimonials,
            'posts' => BlogPost::query()->published()->latest('published_at')->with(['media', 'category', 'author'])->take(3)->get(),
            'faqs' => $faqs,
            'seo' => [
                'title' => settings_t('seo_default_title'),
                'description' => settings_t('seo_default_description'),
                'full_title' => true,
            ],
        ]);
    }
}
