<?php

namespace App\Providers;

use App\Events\InvoiceGenerated;
use App\Listeners\SendInvoiceNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceGenerated::class => [
            SendInvoiceNotification::class,
        ],
    ];
}
