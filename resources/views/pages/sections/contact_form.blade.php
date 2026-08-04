<section class="bg-slate-50 py-14">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        @if (! empty($data['heading']))
            <x-section-heading :title="$data['heading']" :subtitle="$data['subheading'] ?? null" centered />
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-7 lg:p-9">
            @if (session('contact_success'))
                <x-alert type="success" class="mb-5">
                    {{ __('Thank you! Your message has been received — we will get back to you within one business day.') }}
                </x-alert>
            @endif

            <form method="POST" action="{{ route('contact.submit') }}" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                @csrf

                <div class="hp-field" aria-hidden="true">
                    <label for="website_url_hp_p">Website</label>
                    <input type="text" id="website_url_hp_p" name="website_url_hp" tabindex="-1" autocomplete="off" />
                </div>

                <x-form.input name="name" :label="__('Full Name')" required autocomplete="name" />
                <x-form.input name="email" :label="__('Email')" type="email" required autocomplete="email" />
                <x-form.input name="phone" :label="__('Phone')" type="tel" autocomplete="tel" />
                <x-form.select name="service_id" :label="__('Interested Service')" :options="($items ?? collect())->pluck('name', 'id')->all()" />
                <x-form.textarea name="message" :label="__('Message')" required rows="4" class="sm:col-span-2" />
                <x-form.checkbox name="consent" :label="__('I agree to be contacted about my enquiry.')" required class="sm:col-span-2" />

                <div class="sm:col-span-2">
                    <x-button type="submit" variant="primary">{{ __('Send Message') }}</x-button>
                </div>
            </form>
        </div>
    </div>
</section>
