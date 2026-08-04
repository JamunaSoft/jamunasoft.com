<?php

namespace App\Filament\Widgets;

use App\Enums\ContactMessageStatus;
use App\Enums\LeadStatus;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\Lead;
use App\Models\Package;
use App\Models\Portfolio;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = [];

        if (auth()->user()?->can('leads.view')) {
            $stats[] = Stat::make('New leads', Lead::where('status', LeadStatus::New)->count())
                ->description(Lead::count().' total leads')
                ->color('info');

            $stats[] = Stat::make('Overdue follow-ups', Lead::overdueFollowUp()->count())
                ->description('Need attention today')
                ->color(Lead::overdueFollowUp()->exists() ? 'danger' : 'success');
        }

        if (auth()->user()?->can('contact-messages.view')) {
            $stats[] = Stat::make('Unread messages', ContactMessage::where('status', ContactMessageStatus::New)->count())
                ->description(ContactMessage::count().' total messages')
                ->color('warning');
        }

        if (auth()->user()?->can('services.view')) {
            $stats[] = Stat::make('Active services', Service::active()->count());
            $stats[] = Stat::make('Published projects', Portfolio::active()->count());
            $stats[] = Stat::make('Published posts', BlogPost::published()->count())
                ->description(Package::active()->count().' active packages');
        }

        return $stats;
    }
}
