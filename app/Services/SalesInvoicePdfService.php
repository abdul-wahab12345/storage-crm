<?php

namespace App\Services;

use App\Models\SalesInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class SalesInvoicePdfService
{
    public function generate(SalesInvoice $invoice)
    {
        return Pdf::loadView('pdf.sales-invoice', ['invoice' => $invoice])
            ->setPaper('a4', 'portrait');
    }

    public function download(SalesInvoice $invoice): Response
    {
        $pdf = $this->generate($invoice);
        return $pdf->stream("Sales_Invoice_{$invoice->invoice_number}.pdf");
    }
}
