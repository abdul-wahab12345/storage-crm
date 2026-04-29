<?php

namespace App\Services;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotePdfService
{
    public function download(Quote $quote): \Symfony\Component\HttpFoundation\Response
    {
        $quote->load('items');

        return Pdf::loadView('pdf.quote', compact('quote'))
            ->setPaper('a4')
            ->download("quote-{$quote->quote_number}.pdf");
    }
}
