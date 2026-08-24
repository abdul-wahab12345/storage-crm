<?php

namespace App\Filament\Exports;

use App\Models\Lease;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LeaseExporter extends Exporter
{
    protected static ?string $model = Lease::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('tenant.first_name')->label('Tenant First Name'),
            ExportColumn::make('tenant.last_name')->label('Tenant Last Name'),
            ExportColumn::make('tenant.email')->label('Tenant Email'),
            ExportColumn::make('tenant.phone')->label('Tenant Phone'),
            ExportColumn::make('unit.unit_number')->label('Unit Number'),
            ExportColumn::make('unit.facility.name')->label('Facility'),
            ExportColumn::make('move_in_date')->label('Move In Date'),
            ExportColumn::make('move_out_date')->label('Move Out Date'),
            ExportColumn::make('monthly_rate')->label('Monthly Rate'),
            ExportColumn::make('billing_interval_months')->label('Billing Interval (Months)'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('deposit_amount')->label('Deposit Amount'),
            ExportColumn::make('created_at')->label('Created At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your lease export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
