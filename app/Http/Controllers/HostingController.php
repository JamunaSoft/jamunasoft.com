<?php

namespace App\Http\Controllers;

use App\Enums\HostingPlanType;
use App\Models\HostingPlan;
use Illuminate\Contracts\View\View;

class HostingController extends Controller
{
    public function __invoke(): View
    {
        $plans = HostingPlan::query()->active()->ordered()->get()->groupBy(fn (HostingPlan $plan) => $plan->type->value);

        // Preserve the enum case order; keep only types that have plans.
        $groups = collect(HostingPlanType::cases())
            ->filter(fn (HostingPlanType $type) => $plans->has($type->value))
            ->mapWithKeys(fn (HostingPlanType $type) => [$type->value => [
                'type' => $type,
                'plans' => $plans->get($type->value),
            ]]);

        return view('hosting.index', [
            'groups' => $groups,
            'seo' => [
                'title' => __('Web Hosting & Servers'),
                'description' => __('Fast, secure shared hosting, managed hosting, VPS, cloud servers and business email for Bangladeshi businesses.'),
            ],
        ]);
    }
}
