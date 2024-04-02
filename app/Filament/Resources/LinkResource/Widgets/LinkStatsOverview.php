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
            Stat::make('Total Link', fn () => Link::where('user_id', auth()->id())->count())
                ->color('success')
                ->icon('heroicon-o-link')
                ->chart([7, 7]),
            Stat::make(
                'Link Aktif',
                fn () => Link::where('user_id', auth()->id())
                    ->where('status', true)
                    ->where('expired_at', null)
                    ->orWhere('expired_at', '>', now())
                    ->count()
            )
                ->color('warning')
                ->icon('heroicon-o-bolt')
                ->chart([7, 7]),
            Stat::make('Link Kadaluarsa', Link::where('user_id', auth()->id())->where('expired_at', '<=', now())->count())
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->chart([7, 7]),
        ];
    }
}
