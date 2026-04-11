<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class InvoicePdfService
{
    public function generateAndStore(Invoice $invoice): string
    {
        $invoice->load(['tenant', 'lease.unit.facility', 'payments']);

        $html = view('pdf.invoice', compact('invoice'))->render();

        $directory = 'invoices';
        $filename = "invoice-{$invoice->invoice_number}.pdf";
        $path = "{$directory}/{$filename}";

        Storage::makeDirectory($directory);

        $fullPath = storage_path("app/{$path}");

        try {
            Browsershot::html($html)
                ->format('A4')
                ->margins(15, 15, 15, 15)
                ->showBackground()
                ->save($fullPath);
        } catch (\Throwable $e) {
            $this->generateFallbackPdf($html, $fullPath);
        }

        return $path;
    }

    public function stream(Invoice $invoice): string
    {
        $invoice->load(['tenant', 'lease.unit.facility', 'payments']);

        $html = view('pdf.invoice', compact('invoice'))->render();

        try {
            return Browsershot::html($html)
                ->format('A4')
                ->margins(15, 15, 15, 15)
                ->showBackground()
                ->pdf();
        } catch (\Throwable $e) {
            return $html;
        }
    }

    /**
     * Simple HTML-to-file fallback when Browsershot/Chrome is unavailable.
     */
    private function generateFallbackPdf(string $html, string $path): void
    {
        file_put_contents(str_replace('.pdf', '.html', $path), $html);
    }
}
