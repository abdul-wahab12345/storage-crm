<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lease extends Model
{
    use LogsActivity;
    protected $fillable = [
        'unit_id',
        'tenant_id',
        'move_in_date',
        'move_out_date',
        'monthly_rate',
        'billing_day',
        'status',
        'notes',
        'space_details',
        'signed_agreement_path',
    ];

    protected function casts(): array
    {
        return [
            'move_in_date' => 'date',
            'move_out_date' => 'date',
            'monthly_rate' => 'decimal:2',
            'billing_day' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Lease $lease) {
            if (! $lease->billing_day && $lease->move_in_date) {
                $lease->billing_day = $lease->move_in_date->day;
            }

            if (! $lease->monthly_rate && $lease->unit) {
                $lease->monthly_rate = $lease->unit->monthly_price;
            }
        });

        static::created(function (Lease $lease) {
            if ($lease->status === 'active') {
                $lease->unit->update(['status' => 'occupied']);
            }
        });

        static::updated(function (Lease $lease) {
            if ($lease->wasChanged('status') && in_array($lease->status, ['terminated', 'expired'])) {
                $hasOtherActive = $lease->unit->leases()
                    ->where('id', '!=', $lease->id)
                    ->where('status', 'active')
                    ->exists();

                if (! $hasOtherActive) {
                    $lease->unit->update(['status' => 'available']);
                }
            }
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBillingToday($query)
    {
        return $query->where('billing_day', now()->day);
    }
}
