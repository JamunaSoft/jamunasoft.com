<?php

namespace App\Http\Controllers;

use App\Enums\NewsletterStatus;
use App\Http\Requests\NewsletterRequest;
use App\Mail\NewsletterConfirmMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(NewsletterRequest $request): RedirectResponse
    {
        $email = strtolower($request->validated('email'));

        $subscriber = NewsletterSubscriber::query()->where('email', $email)->first();

        if ($subscriber && $subscriber->status === NewsletterStatus::Subscribed) {
            return $this->backToNewsletter()->with('newsletter_status', 'already');
        }

        if ($subscriber) {
            $subscriber->update([
                'status' => NewsletterStatus::Pending,
                'unsubscribed_at' => null,
            ]);
        } else {
            $subscriber = NewsletterSubscriber::create([
                'email' => $email,
                'status' => NewsletterStatus::Pending,
                'source' => 'website_footer',
            ]);
        }

        try {
            Mail::to($subscriber->email)->queue(new NewsletterConfirmMail($subscriber));
        } catch (\Throwable $e) {
            Log::warning('Newsletter confirm email dispatch failed: '.$e->getMessage(), ['subscriber_id' => $subscriber->id]);
        }

        return $this->backToNewsletter()->with('newsletter_status', 'pending');
    }

    public function confirm(string $token): View
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        if (! $subscriber) {
            return $this->statusView('invalid');
        }

        if ($subscriber->status !== NewsletterStatus::Subscribed) {
            $subscriber->update([
                'status' => NewsletterStatus::Subscribed,
                'confirmed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        return $this->statusView('confirmed');
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        if (! $subscriber) {
            return $this->statusView('invalid');
        }

        if ($subscriber->status !== NewsletterStatus::Unsubscribed) {
            $subscriber->update([
                'status' => NewsletterStatus::Unsubscribed,
                'unsubscribed_at' => now(),
            ]);
        }

        return $this->statusView('unsubscribed');
    }

    protected function backToNewsletter(): RedirectResponse
    {
        return redirect()->to(url()->previous().'#newsletter');
    }

    protected function statusView(string $state): View
    {
        return view('newsletter.status', [
            'state' => $state,
            'seo' => [
                'title' => __('Newsletter'),
                'noindex' => true,
            ],
        ]);
    }
}
