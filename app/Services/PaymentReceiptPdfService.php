<?php

namespace App\Services;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentReceiptPdfService
{
    public function generate(Payment $payment)
    {
        $payment->load(['invoice.tenant', 'invoice.lease.unit.facility']);
        $receiptNumber = 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
        return Pdf::loadView('pdf.payment-receipt', compact('payment', 'receiptNumber'))->setPaper('a4');
    }

    public function generateAndStore(Payment $payment): string
    {
        $pdf = $this->generate($payment);
        $receiptNumber = 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
        $path = "payments/{$receiptNumber}.pdf";
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $pdf->output());
        return $path;
    }

    public function download(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $receiptNumber = 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
        return $this->generate($payment)->download("{$receiptNumber}.pdf");
    }
}
