<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Unit extends Model
{
    use LogsActivity;
    protected $fillable = [
        'facility_id',
        'unit_number',
        'size',
        'size_label',
        'monthly_price',
        'status',
        'position_x',
        'position_y',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'position_x' => 'integer',
            'position_y' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLease(): HasOne
    {
        return $this->hasOne(Lease::class)->where('status', 'active')->latestOfMany();
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'available' => 'success',
            'occupied' => 'info',
            'maintenance' => 'warning',
            'overdue' => 'danger',
            default => 'gray',
        };
    }
}
