<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Mail\LeadAdminNotification;
use App\Mail\QuotationConfirmation;
use App\Models\Lead;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuotationFormTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Quote Seeker',
            'phone' => '+8801700000000',
            'email' => 'seeker@example.com',
            'preferred_contact' => 'email',
            'message' => 'I would like a corporate website with a blog.',
            'consent' => '1',
        ], $overrides);
    }

    public function test_quotation_form_validates_required_fields(): void
    {
        $this->from('/request-a-quotation')
            ->post('/request-a-quotation', [])
            ->assertSessionHasErrors(['name', 'phone', 'email', 'preferred_contact', 'message', 'consent']);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_valid_submission_creates_a_lead_with_reference(): void
    {
        Mail::fake();
        Settings::set(['lead_notification_recipients' => 'sales@example.com']);

        $response = $this->post('/request-a-quotation', $this->validPayload());

        $response->assertRedirect(route('quote.thanks'));

        $lead = Lead::first();
        $this->assertNotNull($lead);
        $this->assertSame('Quote Seeker', $lead->name);
        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertSame('quotation_form', $lead->source);
        $this->assertMatchesRegularExpression('/^JS-\d{4}-[A-Z0-9]{6}$/', $lead->reference);

        // Submission is recorded on the activity timeline.
        $this->assertSame(1, $lead->activities()->count());

        Mail::assertQueued(QuotationConfirmation::class);
        Mail::assertQueued(LeadAdminNotification::class);
    }

    public function test_thanks_page_shows_reference_and_requires_session(): void
    {
        $this->post('/request-a-quotation', $this->validPayload());

        // Immediately after submitting, the flashed reference is shown…
        $this->get('/request-a-quotation/thank-you')
            ->assertOk()
            ->assertSee(Lead::first()->reference);

        // …but visiting again without a fresh submission redirects to the form.
        $this->get('/request-a-quotation/thank-you')->assertRedirect(route('quote.create'));
    }

    public function test_lead_references_are_unique(): void
    {
        $references = collect(range(1, 25))->map(fn () => Lead::generateReference());

        $this->assertSame($references->count(), $references->unique()->count());
    }

    public function test_honeypot_submission_creates_no_lead(): void
    {
        $this->post('/request-a-quotation', $this->validPayload(['website_url_hp' => 'bot']))
            ->assertRedirect(route('quote.thanks'));

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_invalid_budget_option_is_rejected(): void
    {
        $this->post('/request-a-quotation', $this->validPayload(['budget' => 'One million dollars']))
            ->assertSessionHasErrors('budget');
    }
}
