<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'category',
        'description',
        'amount',
        'expense_date',
        'facility_id',
        'salary_record_id',
        'vendor',
        'receipt_path',
        'status',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function salaryRecord()
    {
        return $this->belongsTo(SalaryRecord::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            default => 'gray',
        };
    }
}
