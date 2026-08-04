@extends('layouts.app')

@section('content')
    @php
        $whatsappDigits = preg_replace('/\D+/', '', (string) settings('whatsapp_number', ''));
        $mapEmbed = (string) settings('google_map_embed', '');
        $mapValid = str_starts_with($mapEmbed, 'https://');
    @endphp

    @include('partials.page-header', [
        'title' => __('Contact Us'),
        'subtitle' => __('Questions, ideas or a project in mind? We reply within one business day.'),
        'breadcrumbs' => [['label' => __('Contact')]],
    ])

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 sm:px-6 lg:grid-cols-5 lg:px-8">
            {{-- Contact details --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-7">
                    <h2 class="text-lg font-bold text-navy-900">{{ __('Get in touch') }}</h2>
                    <dl class="mt-5 space-y-4 text-sm">
                        @if (settings('office_address'))
                            <div class="flex gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                <div>
                                    <dt class="font-semibold text-navy-900">{{ __('Office') }}</dt>
                                    <dd class="mt-0.5 whitespace-pre-line text-slate-600">{{ settings('office_address') }}</dd>
                                </div>
                            </div>
                        @endif
                        @if (settings('phone_primary'))
                            <div class="flex gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                                <div>
                                    <dt class="font-semibold text-navy-900">{{ __('Phone') }}</dt>
                                    <dd class="mt-0.5 text-slate-600">
                                        <a href="tel:{{ preg_replace('/[^\d+]/', '', (string) settings('phone_primary')) }}" class="hover:text-brand-700">{{ settings('phone_primary') }}</a>
                                        @if (settings('phone_secondary'))
                                            <br /><a href="tel:{{ preg_replace('/[^\d+]/', '', (string) settings('phone_secondary')) }}" class="hover:text-brand-700">{{ settings('phone_secondary') }}</a>
                                        @endif
                                    </dd>
                                </div>
                            </div>
                        @endif
                        @if (settings('email_primary'))
                            <div class="flex gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                <div>
                                    <dt class="font-semibold text-navy-900">{{ __('Email') }}</dt>
                                    <dd class="mt-0.5 text-slate-600">
                                        <a href="mailto:{{ settings('email_primary') }}" class="hover:text-brand-700">{{ settings('email_primary') }}</a>
                                        @if (settings('email_support'))
                                            <br /><a href="mailto:{{ settings('email_support') }}" class="hover:text-brand-700">{{ settings('email_support') }}</a>
                                        @endif
                                    </dd>
                                </div>
                            </div>
                        @endif
                        @if (settings('business_hours'))
                            <div class="flex gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <div>
                                    <dt class="font-semibold text-navy-900">{{ __('Business hours') }}</dt>
                                    <dd class="mt-0.5 text-slate-600">{{ settings('business_hours') }}</dd>
                                </div>
                            </div>
                        @endif
                    </dl>

                    @if ($whatsappDigits)
                        <x-button href="https://wa.me/{{ $whatsappDigits }}" variant="outline" size="sm" class="mt-6 w-full" target="_blank" rel="noopener">
                            {{ __('Chat on WhatsApp') }}
                        </x-button>
                    @endif
                </div>

                @if ($mapValid)
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <iframe
                            src="{{ $mapEmbed }}"
                            title="{{ __('Office location map') }}"
                            class="h-72 w-full"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen
                        ></iframe>
                    </div>
                @endif
            </div>

            {{-- Contact form --}}
            <div class="lg:col-span-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-7 lg:p-9">
                    <h2 class="text-lg font-bold text-navy-900">{{ __('Send us a message') }}</h2>

                    @if (session('contact_success'))
                        <x-alert type="success" class="mt-5">
                            {{ __('Thank you! Your message has been received — we will get back to you within one business day.') }}
                        </x-alert>
                    @endif

                    <form method="POST" action="{{ route('contact.submit') }}" enctype="multipart/form-data" class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        @csrf

                        {{-- Honeypot --}}
                        <div class="hp-field" aria-hidden="true">
                            <label for="website_url_hp">Website</label>
                            <input type="text" id="website_url_hp" name="website_url_hp" tabindex="-1" autocomplete="off" />
                        </div>

                        <x-form.input name="name" :label="__('Full Name')" required autocomplete="name" />
                        <x-form.input name="phone" :label="__('Phone')" type="tel" autocomplete="tel" />
                        <x-form.input name="email" :label="__('Email')" type="email" required autocomplete="email" />
                        <x-form.input name="company" :label="__('Company Name')" autocomplete="organization" />
                        <x-form.select name="service_id" :label="__('Interested Service')" :options="$services->pluck('name', 'id')->all()" />
                        <x-form.input name="subject" :label="__('Subject')" />
                        <x-form.textarea name="message" :label="__('Message')" required rows="5" class="sm:col-span-2" />
                        <x-form.file name="attachment" :label="__('Attachment (optional)')" :hint="__('PDF, DOC or image — max 5 MB.')" class="sm:col-span-2" />
                        <x-form.checkbox name="consent" :label="__('I agree to be contacted about my enquiry and accept the privacy policy.')" required class="sm:col-span-2" />

                        <div class="sm:col-span-2">
                            <x-button type="submit" variant="primary" size="lg">{{ __('Send Message') }}</x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('jsonld')
        <script type="application/ld+json">
            {!! json_encode(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'ProfessionalService',
                'name' => settings('company_name', config('app.name')),
                'url' => url('/'),
                'telephone' => settings('phone_primary'),
                'email' => settings('email_primary'),
                'address' => settings('office_address') ? [
                    '@type' => 'PostalAddress',
                    'streetAddress' => settings('office_address'),
                    'addressCountry' => 'BD',
                ] : null,
            ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush
@endsection
