<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Mail\TicketAdminNotification;
use App\Mail\TicketReplyMail;
use App\Models\User;
use App\Services\TicketService;
use App\Support\Settings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_ticket_notifies_admins(): void
    {
        Mail::fake();
        Settings::set(['ticket_notification_recipients' => 'support@jamunasoft.com'], 'website');

        $customer = User::factory()->create();

        $ticket = app(TicketService::class)->open($customer, 'Website is down', 'My site example.com shows an error.');

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertCount(1, $ticket->messages);
        Mail::assertQueued(TicketAdminNotification::class, fn (TicketAdminNotification $mail) => $mail->hasTo('support@jamunasoft.com'));
    }

    public function test_staff_reply_marks_answered_and_emails_the_customer(): void
    {
        Mail::fake();

        $this->seed(RolePermissionSeeder::class);
        $customer = User::factory()->create();
        $staff = User::factory()->create();
        $staff->assignRole('Support Manager');

        $ticket = app(TicketService::class)->open($customer, 'Question', 'How do I change DNS?');

        app(TicketService::class)->reply($ticket, $staff, 'Use the DNS page under My Domains.', isStaff: true);

        $this->assertSame(TicketStatus::Answered, $ticket->refresh()->status);
        Mail::assertQueued(TicketReplyMail::class, fn (TicketReplyMail $mail) => $mail->hasTo($customer->email));
    }

    public function test_customer_reply_reopens_the_conversation(): void
    {
        Mail::fake();

        $customer = User::factory()->create();
        $ticket = app(TicketService::class)->open($customer, 'Question', 'First message');

        $ticket->update(['status' => TicketStatus::Closed, 'closed_at' => now()]);

        app(TicketService::class)->reply($ticket, $customer, 'Still not working!', isStaff: false);

        $ticket->refresh();
        $this->assertSame(TicketStatus::CustomerReply, $ticket->status);
        $this->assertNull($ticket->closed_at);
    }

    public function test_clients_see_only_their_own_tickets(): void
    {
        $mine = User::factory()->create();
        $other = User::factory()->create();

        $myTicket = app(TicketService::class)->open($mine, 'My problem', 'Help me');
        $otherTicket = app(TicketService::class)->open($other, 'Their problem', 'Help them');

        $this->actingAs($mine)
            ->get('/client/tickets')
            ->assertOk()
            ->assertSee($myTicket->reference)
            ->assertDontSee($otherTicket->reference);

        $this->actingAs($mine)
            ->get("/client/tickets/{$otherTicket->id}")
            ->assertNotFound();
    }

    public function test_client_can_view_own_ticket_thread(): void
    {
        $customer = User::factory()->create();
        $ticket = app(TicketService::class)->open($customer, 'My problem', 'Detailed description here');

        $this->actingAs($customer)
            ->get("/client/tickets/{$ticket->id}")
            ->assertOk()
            ->assertSee('Detailed description here');
    }
}
