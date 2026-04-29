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

        $directory = 'invoices';
        $filename  = "invoice-{$invoice->invoice_number}.pdf";
        $path      = "{$directory}/{$filename}";

        Storage::makeDirectory($directory);

        $pdfContent = Pdf::loadView('pdf.invoice', compact('invoice'))
            ->setPaper('a4')
            ->output();

        $fullPath = storage_path("app/{$path}");
        file_put_contents($fullPath, $pdfContent);

        // Also store a public copy so WhatsApp can fetch the URL
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
