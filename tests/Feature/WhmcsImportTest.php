<?php

namespace Tests\Feature;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whmcs.url' => 'https://whmcs.test/includes/api.php',
            'services.whmcs.identifier' => 'test-id',
            'services.whmcs.secret' => 'test-secret',
        ]);

        Http::fake(function (Request $request) {
            return match ($request['action']) {
                'GetClients' => Http::response([
                    'result' => 'success',
                    'totalresults' => 1,
                    'clients' => ['client' => [[
                        'id' => 74,
                        'firstname' => 'Abu',
                        'lastname' => 'Talha',
                        'email' => 'talha@example.com',
                    ]]],
                ]),
                'GetClientsDetails' => Http::response([
                    'result' => 'success',
                    'companyname' => 'Chicknes',
                    'phonenumber' => '+8801785964272',
                    'address1' => 'Chondongati',
                    'city' => 'Belkuchi',
                    'postcode' => '6740',
                ]),
                'GetInvoices' => Http::response([
                    'result' => 'success',
                    'totalresults' => 1,
                    'invoices' => ['invoice' => [[
                        'id' => $request['status'] === 'Unpaid' ? 349 : 200,
                        'userid' => 74,
                        'total' => $request['status'] === 'Unpaid' ? '51550.00' : '2415.00',
                    ]]],
                ]),
                'GetInvoice' => Http::response($request['invoiceid'] == 349 ? [
                    'result' => 'success',
                    'subtotal' => '51550.00',
                    'credit' => '0.00',
                    'total' => '51550.00',
                    'balance' => '5000.00',
                    'date' => '2024-08-03',
                    'duedate' => '2024-08-31',
                    'datepaid' => '0000-00-00 00:00:00',
                    'items' => ['item' => [[
                        'description' => "Website Design & Development - swiftelevatorbd.com\nwith domain registration",
                        'amount' => '51550.00',
                    ]]],
                ] : [
                    'result' => 'success',
                    'subtotal' => '2415.00',
                    'credit' => '0.00',
                    'total' => '2415.00',
                    'balance' => '0.00',
                    'date' => '2025-03-26',
                    'duedate' => '2025-03-26',
                    'datepaid' => '2025-03-26 17:04:00',
                    'items' => ['item' => [[
                        'description' => 'Domain transfer - chicknes.com',
                        'amount' => '2415.00',
                    ]]],
                ]),
                'GetClientsProducts' => Http::response([
                    'result' => 'success',
                    'totalresults' => 2,
                    'products' => ['product' => [
                        [
                            'id' => 11,
                            'clientid' => 74,
                            'name' => '2GB Hosting',
                            'domain' => 'caringhandsbd.com',
                            'status' => 'Active',
                            'billingcycle' => 'Annually',
                            'recurringamount' => '2000.00',
                            'nextduedate' => '2027-09-14',
                        ],
                        [
                            'id' => 12,
                            'clientid' => 74,
                            'name' => 'Setup Fee',
                            'domain' => '',
                            'status' => 'Active',
                            'billingcycle' => 'One Time',
                            'recurringamount' => '500.00',
                            'nextduedate' => '0000-00-00',
                        ],
                    ]],
                ]),
                default => Http::response(['result' => 'error', 'message' => 'unexpected '.$request['action']], 500),
            };
        });
    }

    public function test_import_fills_profiles_and_creates_invoices_idempotently(): void
    {
        $user = User::factory()->create(['email' => 'talha@example.com']);

        $this->artisan('whmcs:import-billing', ['--history' => true, '--commit' => true])->assertSuccessful();

        $user->refresh();
        $this->assertSame('Chicknes', $user->company_name);
        $this->assertSame('+8801785964272', $user->phone);
        $this->assertSame('Belkuchi', $user->city);

        $unpaid = Invoice::where('meta->whmcs_id', 349)->first();
        $this->assertNotNull($unpaid);
        $this->assertSame(InvoiceStatus::Unpaid, $unpaid->status);
        $this->assertSame('51550.00', (string) $unpaid->total);
        $this->assertSame('46550.00', (string) $unpaid->amount_paid);
        $this->assertSame(5000.0, $unpaid->balance());
        $this->assertSame('2024-08-03', $unpaid->created_at->toDateString());
        $this->assertCount(1, $unpaid->payments);
        $this->assertSame('Website Design & Development - swiftelevatorbd.com', $unpaid->items->first()->displayTitle());

        $paid = Invoice::where('meta->whmcs_id', 200)->first();
        $this->assertSame(InvoiceStatus::Paid, $paid->status);
        $this->assertSame('2025-03-26', $paid->paid_at->toDateString());

        // Preview mode and re-runs never duplicate.
        $this->artisan('whmcs:import-billing', ['--history' => true, '--commit' => true])->assertSuccessful();
        $this->artisan('whmcs:import-billing')->assertSuccessful();
        $this->assertSame(2, Invoice::count());
    }

    public function test_profile_import_never_overwrites_existing_values(): void
    {
        $user = User::factory()->create(['email' => 'talha@example.com']);
        $user->update(['company_name' => 'Already Set Ltd']);

        $this->artisan('whmcs:import-billing', ['--commit' => true])->assertSuccessful();

        $this->assertSame('Already Set Ltd', $user->refresh()->company_name);
        $this->assertSame('Belkuchi', $user->city, 'Empty fields still get filled.');
    }

    public function test_active_whmcs_products_become_recurring_services(): void
    {
        $client = User::factory()->create(['email' => 'talha@example.com']);

        $this->artisan('whmcs:import-services --commit')->assertSuccessful();

        $service = ClientService::where('user_id', $client->id)->first();
        $this->assertNotNull($service);
        $this->assertSame('2GB Hosting', $service->name);
        $this->assertSame('caringhandsbd.com', $service->domain);
        $this->assertSame(BillingCycle::Yearly, $service->billing_cycle);
        $this->assertSame('2000.00', (string) $service->price);
        $this->assertTrue($service->next_due_at->isSameDay('2027-09-14'));

        // One-time items are not recurring — never imported.
        $this->assertSame(1, ClientService::where('user_id', $client->id)->count());

        // Idempotent: a second run imports nothing new.
        $this->artisan('whmcs:import-services --commit')->assertSuccessful();
        $this->assertSame(1, ClientService::where('user_id', $client->id)->count());
    }
}
