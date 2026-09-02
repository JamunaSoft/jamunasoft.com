<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'token', 'user_id', 'billing_profile_id', 'status', 'auto_remind', 'currency', 'subtotal', 'discount',
        'total', 'amount_paid', 'due_at', 'paid_at', 'last_reminded_at',
        'notes', 'meta',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->token ??= str()->random(40);
        });
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'auto_remind' => 'boolean',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = sprintf('INV-%s-%s', now()->format('Y'), strtoupper(str()->random(6)));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function billingProfile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class);
    }

    /**
     * The "Invoiced To" identity: the selected billing profile's company
     * and address, falling back to the client's own details.
     *
     * @return array{company: ?string, name: string, address: ?string, city_line: ?string, email: ?string}
     */
    public function billedTo(): array
    {
        $profile = $this->billingProfile;
        $user = $this->user;

        if ($profile !== null) {
            return [
                'company' => $profile->company_name,
                'name' => $profile->contact_name ?: $user->name,
                'address' => $profile->address,
                'city_line' => trim(implode(', ', array_filter([
                    trim(($profile->city ?? '').' '.($profile->postal_code ?? '')),
                    $profile->country,
                ]))) ?: null,
                'email' => $profile->email ?: $user->email,
            ];
        }

        return [
            'company' => $user->company_name,
            'name' => $user->name,
            'address' => $user->address,
            'city_line' => trim(implode(', ', array_filter([
                trim(($user->city ?? '').' '.($user->postal_code ?? '')),
                $user->country,
            ]))) ?: null,
            'email' => $user->email,
        ];
    }

    /**
     * Everyone this invoice's emails go to: the client's billing inboxes
     * plus the billing profile's own email when it has one.
     *
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return array_values(array_unique(array_filter([
            ...$this->user->billingEmails(),
            $this->billingProfile?->email,
        ])));
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function emailLogs(): MorphMany
    {
        return $this->morphMany(EmailLog::class, 'related');
    }

    /**
     * The public "view & pay" page — accessible without login via the
     * secret token, like quotations.
     */
    public function publicUrl(): string
    {
        return route('invoice.show', ['reference' => $this->reference, 'token' => $this->token]);
    }

    /**
     * WhatsApp click-to-chat link with a prefilled message pointing to the
     * public invoice page. Null when the client has no phone number.
     */
    public function whatsappUrl(): ?string
    {
        $phone = $this->user?->whatsappNumber();

        if ($phone === null) {
            return null;
        }

        $message = sprintf(
            'Dear %s, your invoice %s of ৳%s from %s is due on %s. View & pay online: %s',
            $this->user->name,
            $this->reference,
            number_format((float) $this->total, 2),
            settings('company_name', config('app.name')),
            $this->due_at?->format('d M Y') ?? 'receipt of this message',
            $this->publicUrl(),
        );

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    /**
     * What the client still owes on EARLIER unpaid invoices — shown on
     * invoice PDFs/emails as "Previous due" so one document carries the
     * client's full payable picture.
     */
    public function previousDueAmount(): float
    {
        // Compound ordering so backdated imports (older date, newer id)
        // still count as "previous" for later invoices.
        return round((float) static::query()
            ->where('user_id', $this->user_id)
            ->where(fn (Builder $query) => $query
                ->where('created_at', '<', $this->created_at)
                ->orWhere(fn (Builder $inner) => $inner
                    ->where('created_at', $this->created_at)
                    ->where('id', '<', $this->id)))
            ->unpaid()
            ->sum(DB::raw('total - amount_paid')), 2);
    }

    public function balance(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Unpaid
            && $this->due_at !== null
            && $this->due_at->isPast()
            && ! $this->due_at->isToday();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::Draft, InvoiceStatus::Unpaid]);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Unpaid);
    }
}
