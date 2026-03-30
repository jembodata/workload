<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TaskResource;
use Filament\Widgets\ChartWidget;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Support\RawJs;

class TaskChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Status Task';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 4;
    protected static ?string $minHeight = '320px';

    protected function getData(): array
    {
        $start = $this->filters['startDate'] ?? null;
        $end   = $this->filters['endDate'] ?? null;

        if (!$start || !$end || $start > $end) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $query = Task::query()
            ->whereBetween('tanggal', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay(),
            ]);

        $opened    = (clone $query)->where('status', 'opened')->count();
        $progress  = (clone $query)->where('status', 'progress')->count();
        $closed    = (clone $query)->where('status', 'closed')->count();
        $overdue   = (clone $query)->where('status', 'overdue')->count();
        $postponed = (clone $query)->where('status', 'postponed')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Tasks',
                    'data' => [
                        $opened,
                        $progress,
                        $closed,
                        $overdue,
                        $postponed,
                    ],
                    'backgroundColor' => [
                        '#60a5fa', // Opened
                        '#facc15', // Progress
                        '#4ade80', // Closed
                        '#f87171', // Overdue
                        '#928aadff', // Postponed
                    ],
                ],
            ],
            'labels' => [
                "Opened ({$opened})",
                "Progress ({$progress})",
                "Closed ({$closed})",
                "Overdue ({$overdue})",
                "Postponed ({$postponed})",
            ],
        ];
    }


    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): RawJs
    {
        $tasksUrl = TaskResource::getUrl('index');

        return RawJs::make(<<<JS
            {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: true }
                },
                onClick: (event, elements) => {
                    if (!elements?.length) return;

                    const statusKeys = ['opened', 'progress', 'closed', 'overdue', 'postponed'];
                    const index = elements[0].index;
                    const status = statusKeys[index];

                    if (!status) return;

                    const params = new URLSearchParams();
                    params.set('tableFilters[status][values][0]', status);
                    window.open('{$tasksUrl}?' + params.toString(), '_blank');
                }
            }
        JS);
    }
}
