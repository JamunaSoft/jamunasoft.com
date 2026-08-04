<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Gates a Filament resource behind Spatie permissions.
 * Resources define: protected static string $permissionKey = 'services';
 * Viewing requires `<key>.view` or `<key>.manage`; mutating requires `<key>.manage`.
 */
trait HasPermissionGates
{
    public static function canViewAny(): bool
    {
        return static::userCan('view') || static::userCan('manage');
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::userCan('manage');
    }

    public static function canEdit(Model $record): bool
    {
        return static::userCan('manage');
    }

    public static function canDelete(Model $record): bool
    {
        return static::userCan('manage');
    }

    public static function canDeleteAny(): bool
    {
        return static::userCan('manage');
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::userCan('manage');
    }

    public static function canForceDeleteAny(): bool
    {
        return static::userCan('manage');
    }

    public static function canRestore(Model $record): bool
    {
        return static::userCan('manage');
    }

    public static function canRestoreAny(): bool
    {
        return static::userCan('manage');
    }

    protected static function userCan(string $action): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can(static::$permissionKey.'.'.$action);
    }
}
