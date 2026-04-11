<?php

namespace App\Listeners;

use App\Events\InvoiceGenerated;
use App\Notifications\InvoiceGeneratedNotification;

class SendInvoiceNotification
{
    public function handle(InvoiceGenerated $event): void
    {
        $invoice = $event->invoice->load(['tenant', 'lease.unit.facility']);
        $tenant = $invoice->tenant;

        if ($tenant) {
            $tenant->notify(new InvoiceGeneratedNotification($invoice));
        }
    }
}
