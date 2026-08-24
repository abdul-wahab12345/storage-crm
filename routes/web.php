<?php

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Quote;
use App\Services\InvoicePdfService;
use App\Services\LeaseAgreementPdfService;
use App\Services\PaymentReceiptPdfService;
use App\Services\QuotePdfService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');

Route::get('/webhook/whatsapp', [\App\Http\Controllers\WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [\App\Http\Controllers\WhatsAppWebhookController::class, 'handle'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

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

    Route::get('/leases/{lease}/agreement/pdf', function (Lease $lease) {
        return app(LeaseAgreementPdfService::class)->download($lease);
    })->name('leases.agreement.pdf');

    Route::get('/payments/{payment}/receipt/pdf', function (Payment $payment) {
        return app(PaymentReceiptPdfService::class)->download($payment);
    })->name('payments.receipt.pdf');

    Route::get('/sales-invoices/{sales_invoice}/pdf', function (\App\Models\SalesInvoice $salesInvoice) {
        return app(\App\Services\SalesInvoicePdfService::class)->download($salesInvoice);
    })->name('sales-invoices.pdf');

    Route::get('/salary-records/{salaryRecord}/pdf', function (\App\Models\SalaryRecord $salaryRecord) {
        return app(\App\Services\SalarySlipPdfService::class)->download($salaryRecord);
    })->name('salary-records.pdf');
});
