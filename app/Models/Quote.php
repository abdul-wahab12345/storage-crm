<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use LogsActivity;
    protected $fillable = [
        'quote_number',
        'title',
        'client_name',
        'client_email',
        'client_phone',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'terms_conditions',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'subtotal'    => 'decimal:2',
            'tax_rate'    => 'decimal:2',
            'tax_amount'  => 'decimal:2',
            'total'       => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (empty($quote->quote_number)) {
                $count = static::count() + 1;
                $quote->quote_number = 'QT-' . str_pad($count, 5, '0', STR_PAD_LEFT);
            }

            if (empty($quote->terms_conditions)) {
                $quote->terms_conditions = Setting::get('quote_terms_conditions');
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }
}
