<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        return view('about', [
            'team' => TeamMember::query()->active()->ordered()->with('media')->get(),
            'testimonials' => Testimonial::query()->approved()->ordered()->with('media')->take(4)->get(),
            'seo' => [
                'title' => __('About Us'),
                'description' => str(strip_tags((string) settings_t('about_intro', __('Learn about Jamuna Soft — our story, mission, vision and the team behind our work.'))))->limit(160)->toString(),
            ],
        ]);
    }
}
