<?php

namespace App\Services;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotePdfService
{
    public function generate(Quote $quote)
    {
        $quote->load('items');
        return Pdf::loadView('pdf.quote', compact('quote'))->setPaper('a4');
    }

    public function generateAndStore(Quote $quote): string
    {
        $pdf = $this->generate($quote);
        $path = "quotes/quote-{$quote->quote_number}.pdf";
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $pdf->output());
        return $path;
    }

    public function download(Quote $quote): \Symfony\Component\HttpFoundation\Response
    {
        return $this->generate($quote)->download("quote-{$quote->quote_number}.pdf");
    }
}
