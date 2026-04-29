<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReportsPdfService
{
    public function download(string $view, array $data, string $filename, bool $landscape = true): Response
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4', $landscape ? 'landscape' : 'portrait');

        return $pdf->download($filename);
    }
}
