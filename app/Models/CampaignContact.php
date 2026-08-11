<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignContact extends Model
{
    protected $fillable = [
        'name',
        'whatsapp_number',
    ];
}
