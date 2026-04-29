<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Unit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUnits = Unit::count();
        $occupiedUnits = Unit::where('status', 'occupied')->count();
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        $overdueCount = Invoice::where('status', 'overdue')->count();
        $overdueAmount = Invoice::where('status', 'overdue')->sum('total');

        $pendingCount = Invoice::where('status', 'pending')->count();
        $pendingAmount = Invoice::where('status', 'pending')->sum('total');

        $activeTenants = Tenant::whereHas('activeLeases')->count();

        return [
            Stat::make('Monthly Revenue', \App\Models\Setting::money($monthlyRevenue))
                ->description('Collected this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Occupancy Rate', $occupancyRate . '%')
                ->description("{$occupiedUnits} of {$totalUnits} units occupied")
                ->descriptionIcon('heroicon-m-building-office')
                ->color($occupancyRate >= 80 ? 'success' : ($occupancyRate >= 50 ? 'warning' : 'danger'))
                ->chart([65, 70, 75, 80, 78, 82, $occupancyRate]),

            Stat::make('Overdue Invoices', $overdueCount)
                ->description(\App\Models\Setting::money($overdueAmount) . ' outstanding')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),

            Stat::make('Pending Invoices', $pendingCount)
                ->description(\App\Models\Setting::money($pendingAmount) . ' awaiting payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Active Tenants', $activeTenants)
                ->description('With current leases')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Available Units', Unit::where('status', 'available')->count())
                ->description('Ready to rent')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
