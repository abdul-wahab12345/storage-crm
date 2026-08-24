<?php

namespace App\Services;

use App\Models\SalaryRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class SalarySlipPdfService
{
    public function generate(SalaryRecord $salaryRecord)
    {
        $salaryRecord->load('employee');

        return Pdf::loadView('pdf.salary-slip', ['salaryRecord' => $salaryRecord])
            ->setPaper('a4', 'portrait');
    }

    public function download(SalaryRecord $salaryRecord): Response
    {
        $slipNumber = 'SAL-' . str_pad($salaryRecord->id, 6, '0', STR_PAD_LEFT);
        return $this->generate($salaryRecord)->stream("salary-slip-{$slipNumber}.pdf");
    }
}
