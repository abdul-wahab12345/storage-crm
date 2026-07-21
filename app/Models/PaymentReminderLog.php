<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentReminderLog extends Model
{
    protected $fillable = [
        'invoice_id',
        'days_before',
        'channel',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
