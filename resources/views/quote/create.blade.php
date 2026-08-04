@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Request a Quotation'),
        'subtitle' => __('Tell us about your project — the more detail you share, the more accurate your quotation will be.'),
        'breadcrumbs' => [['label' => __('Request a Quotation')]],
    ])

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('quote.store') }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-7 lg:p-9">
                    @csrf

                    {{-- Honeypot --}}
                    <div class="hp-field" aria-hidden="true">
                        <label for="website_url_hp">Website</label>
                        <input type="text" id="website_url_hp" name="website_url_hp" tabindex="-1" autocomplete="off" />
                    </div>

                    <fieldset>
                        <legend class="text-lg font-bold text-navy-900">{{ __('About you') }}</legend>
                        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-form.input name="name" :label="__('Full Name')" required autocomplete="name" />
                            <x-form.input name="company" :label="__('Company Name')" autocomplete="organization" />
                            <x-form.input name="phone" :label="__('Phone')" type="tel" required autocomplete="tel" />
                            <x-form.input name="email" :label="__('Email')" type="email" required autocomplete="email" />
                            <x-form.select
                                name="preferred_contact"
                                :label="__('Preferred Contact Method')"
                                :options="\App\Http\Requests\QuotationRequest::preferredContactOptions()"
                                required
                            />
                            <x-form.select
                                name="referral_source"
                                :label="__('How did you hear about us?')"
                                :options="collect(\App\Http\Requests\QuotationRequest::referralSourceOptions())->mapWithKeys(fn ($option) => [$option => $option])->all()"
                            />
                        </div>
                    </fieldset>

                    <fieldset class="mt-10">
                        <legend class="text-lg font-bold text-navy-900">{{ __('About your project') }}</legend>
                        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-form.select name="service_id" :label="__('Service')" :options="$services->pluck('name', 'id')->all()" :value="$preselectedServiceId" />
                            <x-form.input name="project_type" :label="__('Project Type')" :hint="__('e.g. new website, redesign, ERP, online store')" />
                            <x-form.input name="existing_url" :label="__('Existing Website URL (if any)')" type="url" placeholder="https://" class="sm:col-span-2" />
                            <x-form.select
                                name="budget"
                                :label="__('Estimated Budget')"
                                :options="collect(\App\Http\Requests\QuotationRequest::budgetOptions())->mapWithKeys(fn ($option) => [$option => $option])->all()"
                            />
                            <x-form.select
                                name="timeline"
                                :label="__('Expected Timeline')"
                                :options="collect(\App\Http\Requests\QuotationRequest::timelineOptions())->mapWithKeys(fn ($option) => [$option => $option])->all()"
                            />
                            <x-form.textarea name="message" :label="__('Project Description')" required rows="6" :value="$prefillMessage" :hint="__('What are you building? Who will use it? What problems should it solve?')" class="sm:col-span-2" />
                        </div>

                        <div class="mt-6">
                            <span class="block text-sm font-semibold text-navy-900">{{ __('Required Features') }}</span>
                            <div class="mt-3 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                @foreach (\App\Http\Requests\QuotationRequest::featureOptions() as $feature)
                                    <x-form.checkbox name="required_features[]" :label="__($feature)" :value="$feature" />
                                @endforeach
                            </div>
                            @error('required_features')
                                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-6">
                            <x-form.file name="attachment" :label="__('Attachment (optional)')" :hint="__('Requirements document, sketch or reference — PDF, DOC or image, max 5 MB.')" />
                        </div>
                    </fieldset>

                    <div class="mt-8 border-t border-slate-100 pt-6">
                        <x-form.checkbox name="consent" :label="__('I agree to be contacted about my quotation request and accept the privacy policy.')" required />
                        <x-button type="submit" variant="primary" size="lg" class="mt-6">{{ __('Submit Quotation Request') }}</x-button>
                    </div>
                </form>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-7">
                    <h2 class="text-base font-bold text-navy-900">{{ __('What happens next?') }}</h2>
                    <ol class="mt-5 space-y-4">
                        @foreach ([
                            __('We review your requirements — usually the same day.'),
                            __('A consultant calls or emails you to clarify details.'),
                            __('You receive a clear quotation with scope, timeline and price.'),
                            __('No obligations — the consultation is completely free.'),
                        ] as $step)
                            <li class="flex gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-xs font-bold text-white">{{ $loop->iteration }}</span>
                                <span class="text-sm leading-relaxed text-slate-600">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="rounded-2xl bg-navy-950 p-7 text-center">
                    <h2 class="text-base font-bold text-white">{{ __('Prefer to talk?') }}</h2>
                    @if (settings('phone_primary'))
                        <p class="mt-3 text-lg font-bold text-white">
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', (string) settings('phone_primary')) }}" class="hover:text-accent-400">{{ settings('phone_primary') }}</a>
                        </p>
                    @endif
                    <p class="mt-1 text-sm text-slate-400">{{ settings('business_hours') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
