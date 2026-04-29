<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'base_salary',
        'bonuses',
        'deductions',
        'total',
        'notes',
        'paid_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'paid_at'    => 'date',
            'base_salary' => 'decimal:2',
            'bonuses'    => 'decimal:2',
            'deductions' => 'decimal:2',
            'total'      => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SalaryRecord $record) {
            $record->total = (float) $record->base_salary
                + (float) $record->bonuses
                - (float) $record->deductions;
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getMonthLabelAttribute(): string
    {
        return \Carbon\Carbon::create($this->year, $this->month)->format('F Y');
    }
}
