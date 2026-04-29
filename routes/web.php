<?php

use App\Models\Invoice;
use App\Models\Quote;
use App\Services\InvoicePdfService;
use App\Services\QuotePdfService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware('auth')->group(function () {
    Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
        return app(InvoicePdfService::class)->download($invoice);
    })->name('invoices.pdf');

    Route::get('/quotes/{quote}/pdf', function (Quote $quote) {
        return app(QuotePdfService::class)->download($quote);
    })->name('quotes.pdf');

    Route::get('/reports/revenue/pdf', function () {
        return app(\App\Filament\Pages\ReportsPage::class)->streamRevenuePdf();
    })->name('reports.revenue.pdf');

    Route::get('/reports/overdue/pdf', function () {
        return app(\App\Filament\Pages\ReportsPage::class)->streamOverduePdf();
    })->name('reports.overdue.pdf');

    Route::get('/reports/payments/pdf', function () {
        $month = request('month', now()->format('Y-m'));
        return app(\App\Filament\Pages\ReportsPage::class)->streamPaymentsPdf($month);
    })->name('reports.payments.pdf');

    Route::get('/reports/salesman/pdf', function () {
        return app(\App\Filament\Pages\ReportsPage::class)->streamSalesmanPdf();
    })->name('reports.salesman.pdf');
});
