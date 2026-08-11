<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoveOutForm extends Model
{
    protected $fillable = [
        'lease_id',
        'move_out_date',
    ];

    protected $casts = [
        'move_out_date' => 'date',
    ];

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }
}
