<?php

namespace App\Services;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentReceiptPdfService
{
    public function download(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $payment->load(['invoice.tenant', 'invoice.lease.unit.facility']);

        $receiptNumber = 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);

        return Pdf::loadView('pdf.payment-receipt', compact('payment', 'receiptNumber'))
            ->setPaper('a4')
            ->download("receipt-{$receiptNumber}.pdf");
    }
}
