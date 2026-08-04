<?php

namespace App\Services;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Mail\LeadAdminNotification;
use App\Mail\QuotationConfirmation;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadService
{
    /**
     * Create a lead from a public form submission, record the initial
     * activity, and queue confirmation / notification emails.
     *
     * @param  array<string, mixed>  $data  Validated lead attributes.
     */
    public function create(array $data, string $source = 'quotation_form'): Lead
    {
        $lead = DB::transaction(function () use ($data, $source): Lead {
            $lead = Lead::create([
                ...$data,
                'reference' => Lead::generateReference(),
                'source' => $source,
                'status' => LeadStatus::New,
            ]);

            $lead->activities()->create([
                'type' => LeadActivityType::Note,
                'body' => 'Lead received from '.str_replace('_', ' ', $source).'.',
            ]);

            return $lead;
        });

        $this->sendEmails($lead);

        return $lead;
    }

    protected function sendEmails(Lead $lead): void
    {
        try {
            if ($lead->email) {
                Mail::to($lead->email)->queue(new QuotationConfirmation($lead));
            }

            foreach ($this->notificationRecipients() as $recipient) {
                Mail::to($recipient)->queue(new LeadAdminNotification($lead));
            }
        } catch (\Throwable $e) {
            // Never let a broken mail configuration lose a lead.
            Log::warning('Lead email dispatch failed: '.$e->getMessage(), ['lead' => $lead->reference]);
        }
    }

    /** @return array<int, string> */
    public function notificationRecipients(): array
    {
        $configured = (string) settings('lead_notification_recipients', settings('contact_form_recipients', ''));

        return collect(explode(',', $configured))
            ->map(fn (string $email) => trim($email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }
}
