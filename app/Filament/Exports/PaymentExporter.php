<?php

namespace App\Filament\Exports;

use App\Models\Payment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('invoice.invoice_number')->label('Invoice Number'),
            ExportColumn::make('invoice.tenant.first_name')->label('Tenant First Name'),
            ExportColumn::make('invoice.tenant.last_name')->label('Tenant Last Name'),
            ExportColumn::make('amount')->label('Amount Paid'),
            ExportColumn::make('method')->label('Payment Method'),
            ExportColumn::make('reference')->label('Reference'),
            ExportColumn::make('paid_at')->label('Paid At'),
            ExportColumn::make('notes')->label('Notes'),
            ExportColumn::make('created_at')->label('Created At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your payment export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
