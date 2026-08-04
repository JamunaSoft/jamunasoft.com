<?php

namespace Tests\Feature;

use App\Enums\NewsletterStatus;
use App\Mail\NewsletterConfirmMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribing_creates_pending_subscriber_and_sends_confirmation(): void
    {
        Mail::fake();

        $this->post('/newsletter', ['email' => 'reader@example.com'])->assertRedirect();

        $subscriber = NewsletterSubscriber::first();
        $this->assertSame(NewsletterStatus::Pending, $subscriber->status);
        $this->assertNotEmpty($subscriber->token);

        Mail::assertQueued(NewsletterConfirmMail::class);
    }

    public function test_confirmation_link_activates_subscription(): void
    {
        $subscriber = NewsletterSubscriber::create(['email' => 'reader@example.com']);

        $this->get('/newsletter/confirm/'.$subscriber->token)->assertOk();

        $this->assertSame(NewsletterStatus::Subscribed, $subscriber->fresh()->status);
        $this->assertNotNull($subscriber->fresh()->confirmed_at);
    }

    public function test_unsubscribe_link_works(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'reader@example.com',
            'status' => NewsletterStatus::Subscribed,
        ]);

        $this->get('/newsletter/unsubscribe/'.$subscriber->token)->assertOk();

        $this->assertSame(NewsletterStatus::Unsubscribed, $subscriber->fresh()->status);
    }

    public function test_invalid_token_shows_friendly_page(): void
    {
        $this->get('/newsletter/confirm/invalid-token')->assertOk()->assertSee(__('Invalid or expired link'));
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->post('/newsletter', ['email' => 'nope'])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }
}
