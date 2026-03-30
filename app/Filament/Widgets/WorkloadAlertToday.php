<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class WorkloadAlertToday extends TableWidget
{
    private const DAILY_CAPACITY_HOURS = 8;

    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 12;

    public function table(Table $table): Table
    {
        $today = Carbon::today()->toDateString();

        return $table
            ->heading('Workload Alert Today')
            ->description('PIC dengan beban kerja harian melebihi kapasitas 8 jam.')
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->query(
                Task::query()
                    ->leftJoin('staff as s', 'tasks.staff_id', '=', 's.id')
                    ->selectRaw('
                        MIN(tasks.id) as id,
                        tasks.staff_id,
                        s.name as staff_name,
                        COUNT(tasks.id) as task_count,
                        SUM(
                            CASE
                                WHEN tasks.is_long_term = 1 THEN COALESCE(tasks.allocation_hours, 0)
                                ELSE COALESCE(tasks.estimasi_jam, 0)
                            END
                        ) as total_hours
                    ')
                    ->whereNotIn('tasks.status', ['closed', 'postponed'])
                    ->where(function (Builder $query) use ($today) {
                        $query
                            ->where(function (Builder $nested) use ($today) {
                                $nested
                                    ->where('tasks.is_long_term', false)
                                    ->whereDate('tasks.tanggal', $today);
                            })
                            ->orWhere(function (Builder $nested) use ($today) {
                                $nested
                                    ->where('tasks.is_long_term', true)
                                    ->whereDate('tasks.tanggal', '<=', $today)
                                    ->whereDate('tasks.tanggal_akhir', '>=', $today);
                            });
                    })
                    ->groupBy('tasks.staff_id', 's.name')
                    ->havingRaw('SUM(CASE WHEN tasks.is_long_term = 1 THEN COALESCE(tasks.allocation_hours, 0) ELSE COALESCE(tasks.estimasi_jam, 0) END) > ?', [self::DAILY_CAPACITY_HOURS])
                    ->orderByDesc('total_hours')
            )
            ->columns([
                Tables\Columns\TextColumn::make('staff_name')
                    ->label('PIC')
                    ->searchable()
                    ->placeholder('-')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('task_count')
                    ->label('Total Tasks')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Total Jam')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 1) . 'h')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('overload_hours')
                    ->label('Overload')
                    ->state(fn($record) => max(0, (float) $record->total_hours - self::DAILY_CAPACITY_HOURS))
                    ->formatStateUsing(fn($state) => '+' . number_format((float) $state, 1) . 'h')
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('openTasks')
                    ->label('Lihat Tasks')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn($record): string => TaskResource::getUrl('index', [
                        'tableSearch' => (string) ($record->staff_name ?? ''),
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Tidak ada overload hari ini')
            ->emptyStateDescription('Semua PIC masih dalam batas kapasitas harian.');
    }
}

