<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PaymentsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(protected string $month) {}

    public function collection()
    {
        [$year, $mon] = array_pad(explode('-', $this->month), 2, null);

        return Payment::with(['invoice.tenant'])
            ->when($year && $mon, fn ($q) => $q->whereYear('paid_at', $year)->whereMonth('paid_at', $mon))
            ->orderByDesc('paid_at')
            ->get();
    }

    public function map($payment): array
    {
        return [
            $payment->invoice?->invoice_number,
            $payment->invoice?->tenant?->full_name,
            ucfirst(str_replace('_', ' ', $payment->method)),
            '$' . number_format($payment->amount, 2),
            $payment->reference ?? '—',
            $payment->paid_at?->format('Y-m-d H:i'),
        ];
    }

    public function headings(): array
    {
        return ['Invoice #', 'Tenant', 'Method', 'Amount', 'Reference', 'Paid At'];
    }

    public function title(): string
    {
        return "Payments {$this->month}";
    }
}
