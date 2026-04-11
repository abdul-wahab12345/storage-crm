<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
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
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (! $invoice->invoice_number) {
                $invoice->invoice_number = 'INV-' . now()->format('Ymd') . '-' . str_pad(
                    static::whereYear('created_at', now()->year)->count() + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                );
            }

            if (! $invoice->total) {
                $invoice->total = $invoice->amount + ($invoice->late_fee ?? 0);
            }
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
