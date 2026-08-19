<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use LogsActivity;
    protected $fillable = [
        'lease_id',
        'tenant_id',
        'invoice_number',
        'amount',
        'late_fee',
        'custom_late_fee',
        'total',
        'due_date',
        'period_start',
        'period_end',
        'status',
        'paid_at',
        'notes',
        'additional_fees',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'late_fee' => 'decimal:2',
            'custom_late_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'due_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
            'additional_fees' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = 'INV-' . now()->format('Ymd') . '-' . str_pad(
                    static::whereYear('created_at', now()->year)->count() + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });

        static::saving(function (Invoice $invoice) {
            $base = (float) ($invoice->amount ?? 0);
            $late = (float) ($invoice->custom_late_fee ?? $invoice->late_fee ?? 0);
            $additional = 0;

            if (is_array($invoice->additional_fees)) {
                foreach ($invoice->additional_fees as $fee) {
                    $additional += (float) ($fee['amount'] ?? 0);
                }
            }

            $invoice->total = $base + $late + $additional;
        });
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'overdue']);
    }

    public function getAmountPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->total - $this->amount_paid;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' && $this->due_date->isPast();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
