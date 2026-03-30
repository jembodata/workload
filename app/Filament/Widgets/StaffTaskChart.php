<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Staff;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StaffTaskChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Task per Staff';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 8;
    protected static ?string $maxHeight = '320px';

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

        $staffs = Staff::withCount(['tasks' => function ($query) use ($start, $end) {
            $query->whereBetween('tanggal', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay(),
            ]);
        }])->get();

        $labels = $staffs->pluck('name')->toArray();
        $values = $staffs->pluck('tasks_count')->map(fn($count) => (int) $count)->toArray();

        $fallbackPalette = [
            '#3b82f6',
            '#22c55e',
            '#f59e0b',
            '#ef4444',
            '#8b5cf6',
            '#06b6d4',
            '#84cc16',
            '#f97316',
            '#ec4899',
            '#14b8a6',
        ];

        $colors = $staffs
            ->values()
            ->map(function ($staff, $index) use ($fallbackPalette) {
                $color = (string) ($staff->color ?? '');

                if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color)) {
                    return $color;
                }

                return $fallbackPalette[$index % count($fallbackPalette)];
            })
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Task',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 0,
                    'borderRadius' => 6,
                    'barThickness' => 18,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
