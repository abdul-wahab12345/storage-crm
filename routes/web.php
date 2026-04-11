<?php

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
    $pdf = app(InvoicePdfService::class)->stream($invoice);

    if (str_starts_with($pdf, '<!DOCTYPE') || str_starts_with($pdf, '<html')) {
        return response($pdf)->header('Content-Type', 'text/html');
    }

    return response($pdf)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', "inline; filename=\"invoice-{$invoice->invoice_number}.pdf\"");
})->name('invoices.pdf');
