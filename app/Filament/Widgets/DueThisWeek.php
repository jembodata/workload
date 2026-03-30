<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DueThisWeek extends StatsOverviewWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 12;

    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();
        $riskEnd = Carbon::today()->addDays(2)->toDateString();
        $weekEnd = Carbon::today()->addDays(7)->toDateString();

        $base = Task::query()
            ->whereNotIn('status', ['closed', 'postponed'])
            ->where(function ($query) {
                $query
                    ->where(function ($nested) {
                        $nested->where('is_long_term', true)->whereNotNull('tanggal_akhir');
                    })
                    ->orWhere(function ($nested) {
                        $nested->where('is_long_term', false)->whereNotNull('tanggal');
                    });
            });

        $dueExpression = "CASE WHEN is_long_term = 1 THEN tanggal_akhir ELSE tanggal END";

        $overdue = (clone $base)
            ->whereRaw("{$dueExpression} < ?", [$today])
            ->count();

        $risk = (clone $base)
            ->whereRaw("{$dueExpression} BETWEEN ? AND ?", [$today, $riskEnd])
            ->count();

        $onTrack = (clone $base)
            ->whereRaw("{$dueExpression} BETWEEN ? AND ?", [Carbon::today()->addDays(3)->toDateString(), $weekEnd])
            ->count();

        $scopeTotal = $overdue + $risk + $onTrack;

        return [
            Stat::make('Due This Week Scope', $scopeTotal)
                ->description('Overdue + due 7 hari ke depan')
                ->color('primary')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('On-Track', $onTrack)
                ->description('Target 3-7 hari ke depan')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Risk', $risk)
                ->description('Target 0-2 hari ke depan')
                ->color('warning')
                ->icon('heroicon-o-exclamation-circle'),

            Stat::make('Overdue', $overdue)
                ->description('Task melewati target')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}

