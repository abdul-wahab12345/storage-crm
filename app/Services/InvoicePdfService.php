<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function generateAndStore(Invoice $invoice): string
    {
        $invoice->load(['tenant', 'lease.unit.facility', 'payments']);

        $path = "invoices/invoice-{$invoice->invoice_number}.pdf";

        $pdfContent = Pdf::loadView('pdf.invoice', compact('invoice'))
            ->setPaper('a4')
            ->output();

        Storage::disk('local')->put($path, $pdfContent);
        Storage::disk('public')->put($path, $pdfContent);

        return $path;
    }

    public function publicUrl(Invoice $invoice): ?string
    {
        $path = "invoices/invoice-{$invoice->invoice_number}.pdf";
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }
        return null;
    }

    public function download(Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $invoice->load(['tenant', 'lease.unit.facility', 'payments']);

        return Pdf::loadView('pdf.invoice', compact('invoice'))
            ->setPaper('a4')
            ->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
