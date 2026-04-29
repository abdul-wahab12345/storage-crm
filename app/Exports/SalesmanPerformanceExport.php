<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SalesmanPerformanceExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function collection()
    {
        return User::where('role', 'salesman')
            ->withCount([
                'leads as leads_new' => fn ($q) => $q->where('status', 'new'),
                'leads as leads_contacted' => fn ($q) => $q->where('status', 'contacted'),
                'leads as leads_qualified' => fn ($q) => $q->where('status', 'qualified'),
                'leads as leads_converted' => fn ($q) => $q->where('status', 'converted'),
                'leads as leads_lost' => fn ($q) => $q->where('status', 'lost'),
                'leads as leads_total',
            ])
            ->get();
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->leads_total,
            $user->leads_new,
            $user->leads_contacted,
            $user->leads_qualified,
            $user->leads_converted,
            $user->leads_lost,
        ];
    }

    public function headings(): array
    {
        return ['Salesman', 'Email', 'Total Leads', 'New', 'Contacted', 'Qualified', 'Converted', 'Lost'];
    }

    public function title(): string
    {
        return 'Salesman Performance';
    }
}
