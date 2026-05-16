<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'source',
        'status',
        'unit_interest',
        'assigned_to',
        'notes',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LeadComment::class)->latest();
    }
}
