<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Enums\LeadActivityType;
use App\Filament\Resources\LeadResource;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    /** @var array<string, mixed> */
    protected array $original = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->original = $this->getRecord()->only(['status', 'assigned_to']);

        return $data;
    }

    protected function afterSave(): void
    {
        $lead = $this->getRecord()->refresh();

        // Record a status-change activity in the lead timeline.
        if (($this->original['status'] ?? null) !== $lead->status) {
            $lead->activities()->create([
                'user_id' => auth()->id(),
                'type' => LeadActivityType::StatusChange,
                'body' => sprintf(
                    'Status changed from "%s" to "%s".',
                    $this->original['status']?->getLabel() ?? '—',
                    $lead->status->getLabel(),
                ),
                'meta' => [
                    'from' => $this->original['status']?->value,
                    'to' => $lead->status->value,
                ],
            ]);
        }

        // Notify the newly assigned user.
        if ($lead->assigned_to && ($this->original['assigned_to'] ?? null) !== $lead->assigned_to) {
            try {
                User::find($lead->assigned_to)?->notify(new LeadAssignedNotification($lead));
            } catch (\Throwable $e) {
                Log::warning('Lead assignment notification failed: '.$e->getMessage());
            }
        }
    }
}
