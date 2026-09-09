<?php

namespace App\Filament\Pages;

use App\Enums\DomainOrderStatus;
use App\Enums\InvoiceStatus;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Ticket;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class Operations extends Page
{
    protected string $view = 'filament.pages.operations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = -10;

    protected static ?string $navigationLabel = 'Operations';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAny(['domains.view', 'domains.manage', 'billing.view', 'billing.manage', 'tickets.view', 'tickets.manage', 'leads.view', 'leads.manage', 'settings.manage']) ?? false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $cards = [];
        $orders = collect();
        if ($user->canAny(['domains.view', 'domains.manage'])) {
            $orders = DomainOrder::query()->whereIn('status', [DomainOrderStatus::Failed, DomainOrderStatus::Processing])->latest()->limit(12)->get();
            $cards[] = ['label' => 'Awaiting domain payment', 'count' => DomainOrder::where('status', DomainOrderStatus::PendingPayment)->count(), 'hint' => 'Confirm received payments before processing', 'url' => '/admin/domain-orders'];
            $cards[] = ['label' => 'Failed domain orders', 'count' => DomainOrder::where('status', DomainOrderStatus::Failed)->count(), 'hint' => 'Review the error before retrying', 'url' => '/admin/domain-orders'];
            $cards[] = ['label' => 'Domains expiring soon', 'count' => Domain::expiringWithin(30)->count(), 'hint' => 'Renewals due within 30 days', 'url' => '/admin/domains'];
        }
        if ($user->canAny(['billing.view', 'billing.manage'])) {
            $cards[] = ['label' => 'Overdue invoices', 'count' => Invoice::where('status', InvoiceStatus::Unpaid)->whereDate('due_at', '<', today())->count(), 'hint' => 'Unpaid invoices past their due date', 'url' => '/admin/invoices'];
        }
        if ($user->canAny(['tickets.view', 'tickets.manage'])) {
            $cards[] = ['label' => 'Tickets awaiting staff', 'count' => Ticket::awaitingStaff()->count(), 'hint' => 'Customers waiting for a response', 'url' => '/admin/tickets'];
        }
        if ($user->canAny(['leads.view', 'leads.manage'])) {
            $cards[] = ['label' => 'Overdue follow-ups', 'count' => Lead::overdueFollowUp()->count(), 'hint' => 'Open leads ready for your next call', 'url' => '/admin/leads'];
        }
        $system = $user->can('settings.manage') ? [
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'queued_jobs' => config('queue.default') === 'database' ? DB::table('jobs')->count() : null,
            'mail_configured' => ! in_array(config('mail.default'), ['log', 'array'], true),
        ] : null;

        return compact('cards', 'orders', 'system');
    }
}
