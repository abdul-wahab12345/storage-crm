<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Notifications\Notifiable;

class Tenant extends Model
{
    use Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
    ];

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function activeLeases(): HasMany
    {
        return $this->hasMany(Lease::class)->where('status', 'active');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return $this->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('total');
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
