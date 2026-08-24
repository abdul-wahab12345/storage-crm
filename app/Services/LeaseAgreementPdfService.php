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

    public function generate(Lease $lease)
    {
        $lease->load(['tenant', 'unit.facility']);
        return Pdf::loadView('pdf.lease-agreement', compact('lease'))->setPaper('a4');
    }

    public function generateAndStore(Lease $lease): string
    {
        $pdf = $this->generate($lease);
        $path = "leases/lease-{$lease->id}.pdf";
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $pdf->output());
        return $path;
    }

    public function download(Lease $lease): \Symfony\Component\HttpFoundation\Response
    {
        return $this->generate($lease)->download("lease-agreement-{$lease->id}.pdf");
    }
}
