<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Invoices';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->with(['tenant', 'lease.unit'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.full_name')
                    ->label('Tenant'),
                Tables\Columns\TextColumn::make('lease.unit.unit_number')
                    ->label('Unit'),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No invoices yet')
            ->emptyStateDescription('Invoices will appear here once billing runs or you create them manually.')
            ->emptyStateIcon('heroicon-o-document-currency-dollar');
    }
}
