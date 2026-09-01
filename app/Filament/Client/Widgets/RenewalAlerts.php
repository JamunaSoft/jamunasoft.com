<?php

namespace App\Filament\Client\Widgets;

use App\Enums\ClientServiceStatus;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class RenewalAlerts extends Widget
{
    protected string $view = 'filament.client.widgets.renewal-alerts';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -3;

    /** Days ahead a renewal counts as "due soon". */
    public const AHEAD_DAYS = 30;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return static::expiringDomains($user)->isNotEmpty()
            || static::dueServices($user)->isNotEmpty();
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            'domains' => static::expiringDomains($user),
            'services' => static::dueServices($user),
        ];
    }

    protected static function expiringDomains(User $user): Collection
    {
        return $user->domains()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(self::AHEAD_DAYS)])
            ->orderBy('expires_at')
            ->get();
    }

    protected static function dueServices(User $user): Collection
    {
        return $user->services()
            ->where('status', ClientServiceStatus::Active)
            ->whereNotNull('next_due_at')
            ->whereBetween('next_due_at', [now()->startOfDay(), now()->addDays(self::AHEAD_DAYS)])
            ->orderBy('next_due_at')
            ->get();
    }
}
