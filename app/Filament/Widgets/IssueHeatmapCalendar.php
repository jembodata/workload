<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;

class IssueHeatmapCalendar extends Widget
{
    protected static string $view = 'filament.widgets.issue-heatmap-calendar';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 12;

    public function getViewData(): array
    {
        $start = Carbon::today()->startOfMonth();
        $end = Carbon::today()->endOfMonth();

        $counts = Task::query()
            ->selectRaw('DATE(tanggal) as day, COUNT(*) as total')
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<=', $end->toDateString())
            ->groupBy('day')
            ->pluck('total', 'day');

        $data = collect($counts)
            ->map(fn($value, $date) => [
                'date' => (string) $date,
                'ts' => CarbonImmutable::parse((string) $date, 'UTC')->startOfDay()->getTimestampMs(),
                'value' => (int) $value,
            ])
            ->values()
            ->all();

        return [
            'title' => 'Task Heatmap',
            'subtitle' => 'Distribusi jumlah task per hari pada bulan ini.',
            'startLabel' => $start->translatedFormat('d M Y'),
            'endLabel' => $end->translatedFormat('d M Y'),
            'startIso' => $start->toDateString(),
            'totalTasks' => array_sum(array_map(fn(array $item) => (int) $item['value'], $data)),
            'calendarData' => $data,
        ];
    }
}
