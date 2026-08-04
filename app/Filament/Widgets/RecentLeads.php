<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentLeads extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('leads.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Leads')
            ->query(Lead::query()->with(['service', 'assignee'])->latest()->limit(8))
            ->columns([
                TextColumn::make('reference'),
                TextColumn::make('name')->description(fn (Lead $record) => $record->company),
                TextColumn::make('service.name')->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('next_follow_up_at')
                    ->label('Follow-up')
                    ->dateTime()
                    ->color(fn (Lead $record) => $record->next_follow_up_at?->isPast() ? 'danger' : null),
                TextColumn::make('created_at')->since(),
            ])
            ->recordUrl(fn (Lead $record) => LeadResource::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }
}
