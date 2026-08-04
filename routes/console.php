<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-follow-up-reminders')->dailyAt('08:00');
Schedule::command('sitemap:generate')->dailyAt('02:00');
Schedule::command('queue:prune-failed --hours=168')->daily();
