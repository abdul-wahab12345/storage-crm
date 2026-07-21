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

        static::saved(function (SalaryRecord $record) {
            if ($record->status === 'paid') {
                \App\Models\Expense::updateOrCreate(
                    [
                        'salary_record_id' => $record->id,
                        'category' => 'payroll',
                    ],
                    [
                        'description' => "Salary - {$record->employee->full_name} ({$record->month_label})",
                        'amount' => $record->total,
                        'expense_date' => $record->paid_at ?? $record->created_at,
                        'status' => 'paid',
                        'paid_at' => $record->paid_at ?? now(),
                        'notes' => $record->notes,
                    ]
                );
            } else {
                // If status is changed back to pending, delete the synced expense? Or just update it to pending?
                // Let's update it to pending so it doesn't get lost if edited manually.
                \App\Models\Expense::where('salary_record_id', $record->id)
                    ->where('category', 'payroll')
                    ->update([
                        'status' => 'pending',
                        'amount' => $record->total,
                    ]);
            }
        });
        
        static::deleted(function (SalaryRecord $record) {
            \App\Models\Expense::where('salary_record_id', $record->id)->delete();
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
