<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TaskResource;
use App\Models\Issue;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class IssueHeatmap extends TableWidget
{
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 12;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Issue Heatmap')
            ->description('Top task/project dengan issue aktif (opened/progress).')
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->query(
                Issue::query()
                    ->join('tasks', 'issues.task_id', '=', 'tasks.id')
                    ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
                    ->selectRaw('
                        MIN(issues.id) as id,
                        tasks.id as task_id,
                        tasks.task_name as task_name,
                        projects.project_name as project_name,
                        SUM(CASE WHEN issues.status IN ("opened", "open") THEN 1 ELSE 0 END) as opened_count,
                        SUM(CASE WHEN issues.status IN ("progress", "in_progress") THEN 1 ELSE 0 END) as progress_count,
                        COUNT(issues.id) as active_issues
                    ')
                    ->whereIn('issues.status', ['opened', 'open', 'progress', 'in_progress'])
                    ->groupBy('tasks.id', 'tasks.task_name', 'projects.project_name')
                    ->orderByDesc('active_issues')
            )
            ->columns([
                Tables\Columns\TextColumn::make('project_name')
                    ->label('Project')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('task_name')
                    ->label('Task')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('opened_count')
                    ->label('Opened')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('progress_count')
                    ->label('Progress')
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('active_issues')
                    ->label('Total Aktif')
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('openTask')
                    ->label('Lihat Task')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn($record): string => TaskResource::getUrl('index', [
                        'tableSearch' => (string) ($record->task_name ?? ''),
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Tidak ada issue aktif')
            ->emptyStateDescription('Semua issue sudah closed atau belum ada issue aktif.');
    }
}

