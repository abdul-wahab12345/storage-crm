<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaChat extends Model
{
    protected $fillable = [
        'contact_phone',
        'contact_name',
        'tenant_id',
        'status',
        'last_message_at',
        'last_message_body',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messages()
    {
        return $this->hasMany(WaMessage::class);
    }
}
