<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Mail\ClientWelcome;
use App\Mail\InvoiceReminder;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ClientProfileToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    protected function makeInvoice(User $user): Invoice
    {
        $invoice = Invoice::create([
            'reference' => Invoice::generateReference(),
            'user_id' => $user->id,
            'status' => InvoiceStatus::Unpaid,
            'subtotal' => 4500,
            'discount' => 500,
            'total' => 4000,
            'due_at' => now()->addDays(7),
        ]);

        $invoice->items()->create([
            'title' => 'Web Hosting — Basic',
            'description' => 'domain: example.com',
            'quantity' => 1,
            'unit_price' => 4500,
            'total' => 4500,
        ]);

        return $invoice;
    }

    public function test_creating_a_client_needs_no_password_and_emails_a_set_password_link(): void
    {
        Mail::fake();
        $admin = $this->superAdmin();

        Livewire::actingAs($admin)
            ->test(ListCustomers::class)
            ->callAction('create', data: [
                'name' => 'New Client',
                'email' => 'new-client@example.com',
            ])
            ->assertHasNoActionErrors();

        $client = User::where('email', 'new-client@example.com')->first();

        $this->assertNotNull($client);
        $this->assertNotNull($client->password);

        Mail::assertQueued(ClientWelcome::class, fn ($mail) => $mail->hasTo('new-client@example.com')
            && str_contains($mail->setPasswordUrl, '/client/'));

        $this->assertSame('client_welcome', $client->emailLogs()->latest()->first()->type);
    }

    public function test_admin_can_resend_the_password_link_from_the_profile(): void
    {
        Mail::fake();
        $admin = $this->superAdmin();
        $client = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ViewCustomer::class, ['record' => $client->id])
            ->callAction('sendPasswordLink');

        Mail::assertQueued(ClientWelcome::class, fn ($mail) => $mail->hasTo($client->email));
    }

    public function test_admin_can_impersonate_a_client_and_return(): void
    {
        $admin = $this->superAdmin();
        $client = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ViewCustomer::class, ['record' => $client->id])
            ->callAction('loginAsClient');

        $this->assertSame($client->id, auth()->id());
        $this->assertSame($admin->id, session('impersonator_id'));
        // AuthenticateSession must see the client's hash, or it force-logs-out.
        $this->assertSame($client->getAuthPassword(), session('password_hash_web'));

        // The client panel opens without demanding a fresh login.
        $this->get('/client')->assertOk();

        $this->get(route('impersonate.leave'))
            ->assertRedirect('/admin/customers/'.$client->id);

        $this->assertSame($admin->id, auth()->id());
        $this->assertFalse(session()->has('impersonator_id'));
    }

    public function test_leave_route_is_blocked_without_an_impersonation_session(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get(route('impersonate.leave'))->assertForbidden();
    }

    public function test_manual_reminder_queues_email_and_stamps_the_invoice(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $user->update(['secondary_email' => 'accounts@example.com']);
        $invoice = $this->makeInvoice($user->refresh());

        app(InvoiceService::class)->sendReminder($invoice);

        Mail::assertQueued(InvoiceReminder::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->hasTo('accounts@example.com'));
        $this->assertNotNull($invoice->refresh()->last_reminded_at);
        $this->assertSame('invoice_reminder', $invoice->emailLogs()->latest()->first()->type);
    }

    public function test_duplicate_copies_items_and_discount_without_emailing(): void
    {
        Mail::fake();

        $invoice = $this->makeInvoice(User::factory()->create());

        $copy = app(InvoiceService::class)->duplicate($invoice);

        $this->assertNotSame($invoice->id, $copy->id);
        $this->assertNotSame($invoice->reference, $copy->reference);
        $this->assertSame(InvoiceStatus::Unpaid, $copy->status);
        $this->assertCount(1, $copy->items);
        $this->assertSame('Web Hosting — Basic', $copy->items->first()->title);
        $this->assertSame('500.00', (string) $copy->discount);
        $this->assertSame('4000.00', (string) $copy->total);
        $this->assertSame('0.00', (string) $copy->amount_paid);

        Mail::assertNothingQueued();
    }
}
