<?php

namespace App\Filament\Resources\ExpenseResource\Widgets;

use App\Filament\Resources\ExpenseResource\Pages\ListExpenses;
use App\Models\Expense;
use App\Models\Setting;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpenseStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListExpenses::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totalAmount = $query->sum('amount');
        
        $paidQuery = clone $query;
        $totalPaid = $paidQuery->where('status', 'paid')->sum('amount');
        
        $pendingQuery = clone $query;
        $totalPending = $pendingQuery->where('status', 'pending')->sum('amount');

        return [
            Stat::make('Total Expenses', Setting::money($totalAmount)),
            Stat::make('Paid Expenses', Setting::money($totalPaid))
                ->color('success'),
            Stat::make('Pending Expenses', Setting::money($totalPending))
                ->color('warning'),
        ];
    }
}
