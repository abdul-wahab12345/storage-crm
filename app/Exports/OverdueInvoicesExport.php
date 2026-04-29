<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class OverdueInvoicesExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function collection()
    {
        return Invoice::overdue()
            ->with(['tenant', 'lease.unit'])
            ->orderBy('due_date')
            ->get();
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->tenant?->full_name,
            $invoice->lease?->unit?->unit_number,
            '$' . number_format($invoice->total, 2),
            $invoice->due_date?->format('Y-m-d'),
            now()->diffInDays($invoice->due_date) . ' days',
        ];
    }

    public function headings(): array
    {
        return ['Invoice #', 'Tenant', 'Unit', 'Amount Due', 'Due Date', 'Days Overdue'];
    }

    public function title(): string
    {
        return 'Overdue Invoices';
    }
}
