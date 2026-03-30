<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Task;
use App\Models\Staff;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters; // trait penting
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 12;
    
    protected function getCards(): array
    {
        $start = $this->filters['startDate'] ?? null;
        $end   = $this->filters['endDate'] ?? null;

        $taskQuery = Task::query();

        if (!$start || !$end || $start > $end) {
            return [
                Card::make('Total Tasks', 0)
                    ->description('Semua task yang terdaftar')
                    ->color('primary')
                    ->icon('heroicon-o-clipboard-document'),

                Card::make('Staff Aktif', Staff::count())
                    ->description('Jumlah staff terdaftar')
                    ->color('success')
                    ->icon('heroicon-o-users'),

                Card::make('Task Selesai', 0)
                    ->description('Task yang sudah selesai')
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),

                Card::make('Overdue Rate', '0%')
                    ->description('Task overdue dalam periode filter')
                    ->color('gray')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }

        $taskQuery->whereBetween('tanggal', [
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->endOfDay(),
        ]);

        $totalTasks = $taskQuery->count();
        $overdueTasks = (clone $taskQuery)->where('status', 'overdue')->count();
        $overdueRate = $totalTasks > 0 ? round(($overdueTasks / $totalTasks) * 100, 1) : 0;

        return [
            Card::make('Total Tasks', $totalTasks)
                ->description('Semua task yang terdaftar')
                ->color('primary')
                ->icon('heroicon-o-clipboard-document'),

            Card::make('Staff Aktif', Staff::count())
                ->description('Jumlah staff terdaftar')
                ->color('success')
                ->icon('heroicon-o-users'),

            Card::make('Task Selesai', (clone $taskQuery)->where('status', 'closed')->count())
                ->description('Task yang sudah selesai')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Card::make('Overdue Rate', "{$overdueRate}%")
                ->description("{$overdueTasks} overdue dari {$totalTasks} task")
                ->color($overdueRate >= 20 ? 'danger' : ($overdueRate >= 10 ? 'warning' : 'success'))
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
