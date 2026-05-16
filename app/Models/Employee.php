<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use LogsActivity;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'department',
        'base_salary',
        'join_date',
        'end_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'join_date'   => 'date',
            'end_date'    => 'date',
            'base_salary' => 'decimal:2',
        ];
    }

    public function salaryRecords(): HasMany
    {
        return $this->hasMany(SalaryRecord::class)->orderByDesc('year')->orderByDesc('month');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getLatestSalaryAttribute(): ?SalaryRecord
    {
        return $this->salaryRecords()->first();
    }
}
