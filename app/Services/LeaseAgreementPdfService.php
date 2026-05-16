<?php

namespace App\Services;

use App\Models\Lease;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class LeaseAgreementPdfService
{
    public function generateAndStore(Lease $lease): string
    {
        $lease->load(['tenant', 'unit.facility']);

        $path = "agreements/agreement-lease-{$lease->id}.pdf";

        $pdfContent = Pdf::loadView('pdf.lease-agreement', compact('lease'))
            ->setPaper('a4')
            ->output();

        Storage::disk('local')->put($path, $pdfContent);

        return $path;
    }

    public function download(Lease $lease): \Symfony\Component\HttpFoundation\Response
    {
        $lease->load(['tenant', 'unit.facility']);

        return Pdf::loadView('pdf.lease-agreement', compact('lease'))
            ->setPaper('a4')
            ->download("lease-agreement-{$lease->id}.pdf");
    }
}
