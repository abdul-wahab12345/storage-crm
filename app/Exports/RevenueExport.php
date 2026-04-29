<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RevenueExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected string $year) {}

    public function array(): array
    {
        $year = (int) $this->year;
        $data = Invoice::paid()
            ->whereYear('paid_at', $year)
            ->selectRaw('MONTH(paid_at) as month, SUM(total) as total, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $rows[] = [
                Carbon::create($year, $m)->format('F Y'),
                round($data[$m] ?? 0, 2),
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Month', 'Revenue ($)'];
    }

    public function title(): string
    {
        return "Revenue {$this->year}";
    }
}
