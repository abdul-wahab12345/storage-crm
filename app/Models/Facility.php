<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Facility extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'late_fee_type',
        'late_fee_amount',
        'late_fee_grace_days',
        'webhook_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'late_fee_amount' => 'decimal:2',
            'late_fee_grace_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function leases(): HasManyThrough
    {
        return $this->hasManyThrough(Lease::class, Unit::class);
    }

    public function getOccupancyRateAttribute(): float
    {
        $total = $this->units()->count();
        if ($total === 0) return 0;
        $occupied = $this->units()->where('status', 'occupied')->count();
        return round(($occupied / $total) * 100, 1);
    }

    public function getAvailableUnitsCountAttribute(): int
    {
        return $this->units()->where('status', 'available')->count();
    }
}
