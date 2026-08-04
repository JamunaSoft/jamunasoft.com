<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Notifications\FollowUpReminderNotification;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    protected $signature = 'app:send-follow-up-reminders';

    protected $description = 'Email each assignee a digest of their overdue lead follow-ups';

    public function handle(): int
    {
        $leads = Lead::overdueFollowUp()
            ->whereNotNull('assigned_to')
            ->with('assignee')
            ->get()
            ->groupBy('assigned_to');

        foreach ($leads as $group) {
            $assignee = $group->first()->assignee;

            if ($assignee) {
                $assignee->notify(new FollowUpReminderNotification($group));
            }
        }

        $this->info('Sent reminders for '.$leads->flatten()->count().' overdue leads.');

        return self::SUCCESS;
    }
}
