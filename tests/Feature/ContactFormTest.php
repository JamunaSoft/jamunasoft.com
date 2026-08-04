<?php

namespace Tests\Feature;

use App\Mail\ContactAdminNotification;
use App\Mail\ContactConfirmation;
use App\Models\ContactMessage;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Person',
            'email' => 'person@example.com',
            'message' => 'I need a website for my business.',
            'consent' => '1',
        ], $overrides);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $this->from('/contact')
            ->post('/contact', [])
            ->assertRedirect('/contact')
            ->assertSessionHasErrors(['name', 'email', 'message', 'consent']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_contact_form_rejects_invalid_email(): void
    {
        $this->post('/contact', $this->validPayload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
    }

    public function test_valid_submission_is_stored_and_emails_are_queued(): void
    {
        Mail::fake();
        Settings::set(['contact_form_recipients' => 'admin@example.com']);

        $this->post('/contact', $this->validPayload())
            ->assertRedirect(route('contact.form'))
            ->assertSessionHas('contact_success');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Test Person',
            'email' => 'person@example.com',
        ]);

        Mail::assertQueued(ContactConfirmation::class);
        Mail::assertQueued(ContactAdminNotification::class);
    }

    public function test_honeypot_submission_is_silently_dropped(): void
    {
        $this->post('/contact', $this->validPayload(['website_url_hp' => 'https://spam.example']))
            ->assertRedirect(route('contact.form'));

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_attachment_is_stored_on_private_disk(): void
    {
        Storage::fake('local');

        $this->post('/contact', $this->validPayload([
            'attachment' => UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf'),
        ]))->assertRedirect(route('contact.form'));

        $message = ContactMessage::first();
        $this->assertNotNull($message->attachment_path);
        Storage::disk('local')->assertExists($message->attachment_path);
    }

    public function test_disallowed_attachment_types_are_rejected(): void
    {
        $this->post('/contact', $this->validPayload([
            'attachment' => UploadedFile::fake()->create('evil.php', 10, 'text/x-php'),
        ]))->assertSessionHasErrors('attachment');
    }
}
