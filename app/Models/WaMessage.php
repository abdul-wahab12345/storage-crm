<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaMessage extends Model
{
    protected $fillable = [
        'wa_chat_id',
        'wa_message_id',
        'direction',
        'type',
        'body',
        'media_url',
        'media_filename',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function chat()
    {
        return $this->belongsTo(WaChat::class, 'wa_chat_id');
    }
}
