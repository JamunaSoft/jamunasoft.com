<?php

namespace Tests\Feature;

use App\Enums\BillingCycle;
use App\Enums\ClientServiceStatus;
use App\Mail\InvoiceBundle;
use App\Mail\InvoiceCreated;
use App\Models\BillingProfile;
use App\Models\ClientService;
use App\Models\User;
use App\Services\RecurringBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BillingProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function makeService(User $user, string $name, float $price, ?int $profileId = null): ClientService
    {
        return ClientService::create([
            'user_id' => $user->id,
            'billing_profile_id' => $profileId,
            'name' => $name,
            'billing_cycle' => BillingCycle::Monthly,
            'price' => $price,
            'status' => ClientServiceStatus::Active,
            'next_due_at' => now()->addDays(3),
        ]);
    }

    public function test_one_owner_two_companies_gets_two_invoices_in_one_email(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['name' => 'Md. Sohel Islam']);
        $owner->forceFill(['company_name' => 'Mr. Baker Cake & Pastry'])->save();

        $secondCompany = BillingProfile::create([
            'user_id' => $owner->id,
            'company_name' => 'Second Company Ltd.',
        ]);

        $this->makeService($owner, 'Web Hosting', 3500);
        $this->makeService($owner, 'FB Boosting', 30000, $secondCompany->id);

        $invoices = collect(app(RecurringBillingService::class)->generateDueInvoices());

        // Two invoices — one per company.
        $this->assertCount(2, $invoices);
        $default = $invoices->firstWhere('billing_profile_id', null);
        $profiled = $invoices->firstWhere('billing_profile_id', $secondCompany->id);
        $this->assertSame('Mr. Baker Cake & Pastry', $default->billedTo()['company']);
        $this->assertSame('Second Company Ltd.', $profiled->billedTo()['company']);

        // …but ONE bundled email, since both go to the same inbox.
        Mail::assertQueued(InvoiceBundle::class, fn (InvoiceBundle $mail) => count($mail->invoices) === 2
            && $mail->hasTo($owner->email));
        Mail::assertNotQueued(InvoiceCreated::class);

        // The bundle attaches one PDF per invoice.
        $bundle = new InvoiceBundle($invoices->all());
        $this->assertCount(2, $bundle->attachments());
    }

    public function test_profile_with_its_own_email_gets_a_separate_mail(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $profile = BillingProfile::create([
            'user_id' => $owner->id,
            'company_name' => 'Accounts Dept Co.',
            'email' => 'accounts@second-co.com',
        ]);

        $this->makeService($owner, 'Hosting A', 1000);
        $this->makeService($owner, 'Hosting B', 2000, $profile->id);

        app(RecurringBillingService::class)->generateDueInvoices();

        // Different recipient sets → two individual emails, no bundle.
        Mail::assertNotQueued(InvoiceBundle::class);
        Mail::assertQueued(InvoiceCreated::class, 2);
        Mail::assertQueued(InvoiceCreated::class, fn ($mail) => $mail->hasTo('accounts@second-co.com'));
    }

    public function test_public_page_shows_the_profile_company(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $profile = BillingProfile::create([
            'user_id' => $owner->id,
            'company_name' => 'Second Company Ltd.',
            'address' => 'House 1, Road 2, Dhaka',
        ]);

        $this->makeService($owner, 'Hosting', 1000, $profile->id);
        $invoice = collect(app(RecurringBillingService::class)->generateDueInvoices())->first();

        $this->get($invoice->publicUrl())
            ->assertOk()
            ->assertSee('Second Company Ltd.');

        $this->assertStringContainsString('Second Company Ltd.', (new InvoiceCreated($invoice))->render());
    }
}
