<?php

namespace App\Filament\Resources\LinkResource\Widgets;

use App\Models\Link;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LinkStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Link', fn () => Link::count())
                ->color('success')
                ->descriptionIcon('heroicon-o-link')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
            Stat::make('Link Aktif', fn () => Link::where('status', true)->count())
                ->color('waring')
                ->descriptionIcon('heroicon-o-check-circle')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
            Stat::make('Link Kadaluarsa', Link::where('expired_at', '<=', now())->count())
                ->color('danger')
                ->descriptionIcon('heroicon-o-x-circle')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
        ];
    }
}
