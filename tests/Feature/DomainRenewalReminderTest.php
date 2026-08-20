<?php

namespace Tests\Feature;

use App\Mail\DomainRenewalReminder;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DomainRenewalReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_is_sent_once_per_threshold(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $domain = Domain::create([
            'name' => 'expiring-soon.com',
            'user_id' => $user->id,
            'expires_at' => now()->addDays(10),
        ]);

        $this->artisan('domains:send-renewal-reminders')->assertSuccessful();

        Mail::assertQueued(DomainRenewalReminder::class, fn (DomainRenewalReminder $mail) => $mail->hasTo($user->email));
        $this->assertSame(15, $domain->refresh()->last_reminder_days);

        // Same threshold again — no duplicate.
        $this->artisan('domains:send-renewal-reminders')->assertSuccessful();
        Mail::assertQueuedCount(1);

        // Crossing a tighter threshold sends again.
        $domain->update(['expires_at' => now()->addDays(2)]);
        $this->artisan('domains:send-renewal-reminders')->assertSuccessful();
        Mail::assertQueuedCount(2);
        $this->assertSame(3, $domain->refresh()->last_reminder_days);
    }

    public function test_no_reminder_outside_the_window_and_rearm_after_renewal(): void
    {
        Mail::fake();

        $domain = Domain::create([
            'name' => 'fresh.com',
            'user_id' => User::factory()->create()->id,
            'expires_at' => now()->addDays(300),
            'last_reminder_days' => 15,
        ]);

        $this->artisan('domains:send-renewal-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
        $this->assertNull($domain->refresh()->last_reminder_days);
    }
}
