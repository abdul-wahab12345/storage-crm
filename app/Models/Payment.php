<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use LogsActivity;
    protected $fillable = [
        'invoice_id',
        'amount',
        'method',
        'reference',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Payment $payment) {
            $invoice = $payment->invoice;
            $totalPaid = $invoice->payments()->sum('amount');

            if ($totalPaid >= $invoice->total) {
                $invoice->markAsPaid();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
