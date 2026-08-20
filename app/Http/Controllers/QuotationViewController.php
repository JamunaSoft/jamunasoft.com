<?php

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuotationViewController extends Controller
{
    public function show(string $reference, string $token): View
    {
        return view('quotations.show', [
            'quotation' => $this->resolve($reference, $token),
            'seo' => [
                'title' => __('Quotation :reference', ['reference' => $reference]),
                'description' => __('Review and accept your quotation online.'),
            ],
        ]);
    }

    public function respond(Request $request, QuotationService $service, string $reference, string $token): RedirectResponse
    {
        $quotation = $this->resolve($reference, $token);

        $request->validate(['decision' => ['required', 'in:accept,decline']]);

        if ($quotation->status !== QuotationStatus::Sent || $quotation->isExpired()) {
            return redirect($quotation->publicUrl());
        }

        $service->respond($quotation, $request->input('decision') === 'accept');

        return redirect($quotation->publicUrl());
    }

    protected function resolve(string $reference, string $token): Quotation
    {
        return Quotation::query()
            ->where('reference', $reference)
            ->where('token', $token)
            ->where('status', '!=', QuotationStatus::Draft)
            ->firstOrFail();
    }
}
