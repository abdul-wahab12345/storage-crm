<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Models\Setting;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListPayments::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totalAmount = $query->sum('amount');
        $totalCount = $query->count();
        
        $cashQuery = clone $query;
        $totalCash = $cashQuery->where('method', 'cash')->sum('amount');
        
        $cardQuery = clone $query;
        $totalCard = $cardQuery->where('method', 'card')->sum('amount');

        $bankQuery = clone $query;
        $totalBank = $bankQuery->where('method', 'bank_transfer')->sum('amount');

        return [
            Stat::make('Total Payments', Setting::money($totalAmount))
                ->description("From {$totalCount} transactions"),
            Stat::make('Cash Payments', Setting::money($totalCash)),
            Stat::make('Card Payments', Setting::money($totalCard)),
            Stat::make('Bank Transfers', Setting::money($totalBank)),
        ];
    }
}
