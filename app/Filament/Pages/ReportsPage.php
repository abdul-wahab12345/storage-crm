<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RevenueExport;
use App\Exports\OverdueInvoicesExport;
use App\Exports\SalesmanPerformanceExport;
use App\Exports\PaymentsExport;

class ReportsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Reports';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.reports-page';

    public string $revenueYear;

    public string $paymentsMonth;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->revenueYear = (string) now()->year;
        $this->paymentsMonth = now()->format('Y-m');
    }

    public function getRevenueChartData(): array
    {
        $year = (int) $this->revenueYear;
        $data = Invoice::paid()
            ->whereYear('paid_at', $year)
            ->selectRaw('MONTH(paid_at) as month, SUM(total) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $labels = [];
        $values = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = Carbon::create($year, $m)->format('M');
            $values[] = round($data[$m] ?? 0, 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function getOverdueInvoices(): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::overdue()
            ->with(['tenant', 'lease.unit'])
            ->orderBy('due_date')
            ->get();
    }

    public function getPaymentBreakdown(): \Illuminate\Support\Collection
    {
        [$year, $month] = array_pad(explode('-', $this->paymentsMonth), 2, null);

        return Payment::query()
            ->when($year && $month, fn ($q) => $q->whereYear('paid_at', $year)->whereMonth('paid_at', $month))
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('method')
            ->get();
    }

    public function getSalesmanStats(): \Illuminate\Support\Collection
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

    public function getAvailableYears(): array
    {
        $years = Invoice::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        return array_combine($years, $years) ?: [(string) now()->year => (string) now()->year];
    }

    public function exportRevenue(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new RevenueExport($this->revenueYear), "revenue-{$this->revenueYear}.xlsx");
    }

    public function exportOverdue(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new OverdueInvoicesExport, 'overdue-invoices.xlsx');
    }

    public function exportPayments(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new PaymentsExport($this->paymentsMonth), "payments-{$this->paymentsMonth}.xlsx");
    }

    public function exportSalesman(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new SalesmanPerformanceExport, 'salesman-performance.xlsx');
    }

    public function exportRevenuePdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $chart = $this->getRevenueChartData();
        $year  = $this->revenueYear;
        $pdf   = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.revenue', compact('chart', 'year'))
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "revenue-{$year}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    public function exportOverduePdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $overdue = $this->getOverdueInvoices();
        $pdf     = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.overdue', compact('overdue'))
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'overdue-invoices.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function exportPaymentsPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $payments = $this->getPaymentBreakdown();
        $month    = $this->paymentsMonth;
        $pdf      = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.payments', compact('payments', 'month'))
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "payments-{$month}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    public function exportSalesmanPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $salesmen = $this->getSalesmanStats();
        $pdf      = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.salesman', compact('salesmen'))
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'salesman-performance.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    // Called by web routes (outside Livewire context)
    public function streamRevenuePdf(): \Symfony\Component\HttpFoundation\Response
    {
        $this->revenueYear = (string) now()->year;
        $chart = $this->getRevenueChartData();
        $year  = $this->revenueYear;
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.revenue', compact('chart', 'year'))
            ->setPaper('a4', 'landscape')
            ->download("revenue-{$year}.pdf");
    }

    public function streamOverduePdf(): \Symfony\Component\HttpFoundation\Response
    {
        $overdue = $this->getOverdueInvoices();
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.overdue', compact('overdue'))
            ->setPaper('a4', 'landscape')
            ->download('overdue-invoices.pdf');
    }

    public function streamPaymentsPdf(string $month): \Symfony\Component\HttpFoundation\Response
    {
        $this->paymentsMonth = $month;
        $payments = $this->getPaymentBreakdown();
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.payments', compact('payments', 'month'))
            ->setPaper('a4', 'landscape')
            ->download("payments-{$month}.pdf");
    }

    public function streamSalesmanPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $salesmen = $this->getSalesmanStats();
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.salesman', compact('salesmen'))
            ->setPaper('a4', 'landscape')
            ->download('salesman-performance.pdf');
    }
}
