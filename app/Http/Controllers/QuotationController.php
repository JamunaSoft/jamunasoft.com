<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuotationRequest;
use App\Models\Service;
use App\Services\LeadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    public function create(Request $request): View
    {
        $services = Service::query()->active()->ordered()->get();

        // Support prefill links such as /request-a-quotation?service=hosting&plan=Basic
        $preselectedServiceId = null;

        if ($serviceParam = $request->query('service')) {
            $preselectedServiceId = $services->first(
                fn (Service $service) => $service->slug === $serviceParam
                    || Str::contains(Str::lower($service->name), Str::lower($serviceParam)),
            )?->id;
        }

        $prefillMessage = null;

        if ($plan = $request->query('plan')) {
            $prefillMessage = __('I am interested in the “:plan” plan. ', ['plan' => $plan]);
        } elseif ($package = $request->query('package')) {
            $prefillMessage = __('I am interested in the “:plan” package. ', ['plan' => Str::headline($package)]);
        }

        return view('quote.create', [
            'services' => $services,
            'preselectedServiceId' => $preselectedServiceId,
            'prefillMessage' => $prefillMessage,
            'seo' => [
                'title' => __('Request a Quotation'),
                'description' => __('Tell us about your project and get a free, no-obligation quotation from Jamuna Soft within one business day.'),
            ],
        ]);
    }

    public function store(QuotationRequest $request, LeadService $leadService): RedirectResponse
    {
        // Honeypot: bots fill the hidden field. Pretend success, store nothing.
        if ($request->filled('website_url_hp')) {
            return redirect()
                ->route('quote.thanks')
                ->with('lead_reference', 'JS-'.now()->format('Y').'-RECEIVED');
        }

        $data = $request->safe()->except(['attachment', 'consent']);

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('attachments/quotes', 'local');
        }

        $lead = $leadService->create($data, 'quotation_form');

        return redirect()
            ->route('quote.thanks')
            ->with('lead_reference', $lead->reference);
    }

    public function thanks(): View|RedirectResponse
    {
        if (! session()->has('lead_reference')) {
            return redirect()->route('quote.create');
        }

        return view('quote.thanks', [
            'reference' => session('lead_reference'),
            'seo' => [
                'title' => __('Thank You'),
                'noindex' => true,
            ],
        ]);
    }
}
