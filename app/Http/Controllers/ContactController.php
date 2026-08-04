<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactAdminNotification;
use App\Mail\ContactConfirmation;
use App\Models\ContactMessage;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact', [
            'services' => Service::query()->active()->ordered()->get(),
            'seo' => [
                'title' => __('Contact Us'),
                'description' => __('Get in touch with Jamuna Soft — call, WhatsApp, email or visit our office. We reply within one business day.'),
            ],
        ]);
    }

    public function store(ContactMessageRequest $request): RedirectResponse
    {
        // Honeypot: bots fill the hidden field. Pretend success, store nothing.
        if ($request->filled('website_url_hp')) {
            return redirect()
                ->route('contact.form')
                ->with('contact_success', true);
        }

        $data = $request->safe()->except(['attachment', 'consent']);

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('attachments/contact', 'local');
        }

        $message = ContactMessage::create($data);

        try {
            Mail::to($message->email)->queue(new ContactConfirmation($message));

            $recipients = collect(explode(',', (string) settings('contact_form_recipients', '')))
                ->map(fn (string $email) => trim($email))
                ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL));

            foreach ($recipients as $recipient) {
                Mail::to($recipient)->queue(new ContactAdminNotification($message));
            }
        } catch (\Throwable $e) {
            Log::warning('Contact email dispatch failed: '.$e->getMessage(), ['contact_message_id' => $message->id]);
        }

        return redirect()
            ->route('contact.form')
            ->with('contact_success', true);
    }
}
