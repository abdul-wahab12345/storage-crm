<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FacilityMapWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            FacilityMapWidget::class,
            RecentActivityWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
