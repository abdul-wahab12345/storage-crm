<?php

namespace App\Services;

use App\Models\MoveOutForm;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MoveOutFormPdfService
{
    public function download(MoveOutForm $form): StreamedResponse
    {
        $fileName = 'move_out_form_' . $form->lease->tenant->first_name . '_' . $form->lease->unit->unit_number . '.pdf';
        
        $pdf = Pdf::loadView('pdf.move-out-form', [
            'form' => $form,
            'lease' => $form->lease,
            'tenant' => $form->lease->tenant,
            'unit' => $form->lease->unit,
        ])->setPaper('a4');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }
}
