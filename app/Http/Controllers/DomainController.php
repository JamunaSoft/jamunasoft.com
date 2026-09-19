<?php

namespace App\Http\Controllers;

use App\Enums\DomainOrderType;
use App\Http\Requests\DomainOrderRequest;
use App\Models\DomainOrder;
use App\Models\Tld;
use App\Services\DomainOrderService;
use App\Services\DomainSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index(Request $request, DomainSearchService $search): View
    {
        $query = (string) $request->query('q', '');
        $results = null;
        $searchError = null;

        if ($query !== '') {
            ['results' => $results, 'error' => $searchError] = $search->search($query);
        }

        return view('domains.index', [
            'tlds' => Tld::query()->active()->ordered()->get(),
            'query' => $query,
            'results' => $results,
            'searchError' => $searchError,
            'billingProfiles' => $request->user()?->billingProfiles()->orderBy('company_name')->get() ?? collect(),
            'seo' => [
                'title' => __('Domain Registration'),
                'description' => __('Search and register your perfect domain name at the best prices in Bangladesh — pay easily with bKash or bank transfer.'),
            ],
        ]);
    }

    public function order(DomainOrderRequest $request, DomainSearchService $search, DomainOrderService $orders): RedirectResponse
    {
        // Honeypot: bots fill the hidden field. Pretend success, store nothing.
        if ($request->filled('website_url_hp')) {
            return redirect()->route('domains.index');
        }

        $domain = $search->sanitize((string) $request->validated('domain'));

        if (! $search->isValidDomain($domain)) {
            return back()->withInput()->withErrors(['domain' => __('That does not look like a valid domain name.')]);
        }

        if (Tld::matching($domain) === null) {
            return back()->withInput()->withErrors(['domain' => __('We do not currently offer this domain extension.')]);
        }

        // A fresh availability check so obviously-taken domains never become orders.
        ['results' => $results, 'error' => $error] = $search->search($domain);

        if ($error !== null) {
            return back()->withInput()->withErrors(['domain' => $error]);
        }

        $result = $results[0] ?? null;

        if ($result === null || $result['available'] !== true) {
            return back()->withInput()->withErrors(['domain' => __('Sorry, this domain is not available for registration.')]);
        }

        if ($result['premium'] === true) {
            return back()->withInput()->withErrors(['domain' => __('This is a premium domain — please contact us for a price.')]);
        }

        $customer = [
            'name' => (string) $request->validated('name'),
            'email' => (string) $request->validated('email'),
            'phone' => $request->validated('phone'),
            'user_id' => $request->user()?->id,
        ];

        if ($request->user() && $request->validated('contact_option') === 'existing') {
            $profile = $request->user()
                ->billingProfiles()
                ->whereKey($request->validated('billing_profile_id'))
                ->firstOrFail();

            $customer = [
                'name' => $profile->contact_name ?: $profile->company_name,
                'email' => $profile->email ?: $request->user()->email,
                'phone' => $profile->phone ?: $request->user()->phone,
                'user_id' => $request->user()->id,
            ];
        }

        $order = $orders->create(
            customer: $customer,
            domainName: $domain,
            type: DomainOrderType::Register,
            years: (int) $request->validated('years'),
        );

        return redirect()->route('domains.order.status', $order->reference);
    }

    public function status(string $reference): View
    {
        $order = DomainOrder::query()->where('reference', $reference)->firstOrFail();

        return view('domains.status', [
            'order' => $order,
            'seo' => [
                'noindex' => true,
                'title' => __('Domain Order :reference', ['reference' => $order->reference]),
                'description' => __('Track the status of your domain order.'),
            ],
        ]);
    }
}
