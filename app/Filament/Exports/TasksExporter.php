<?php

namespace App\Filament\Exports;

use App\Models\Task;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TasksExporter extends Exporter
{
    protected static ?string $model = Task::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('project.project_name')
                ->label('Project')
                ->getStateUsing(fn($record) => $record->project?->project_name ?? '-'),

            ExportColumn::make('task_name')
                ->label('Task Name'),

            ExportColumn::make('input')
                ->label('Input'),

            ExportColumn::make('output')
                ->label('Output'),

            ExportColumn::make('tanggal')
                ->label('Target'),

            ExportColumn::make('estimasi_jam')
                ->label('Estimasi Jam'),

            ExportColumn::make('id'),

            ExportColumn::make('status')
                ->label('Evaluasi Efektivitas'),

            ExportColumn::make('staff.name')
                ->label('PIC'),

            ExportColumn::make('is_long_term')
                ->label('Long Term'),

            ExportColumn::make('tanggal')
                ->label('Start Date'),

            ExportColumn::make('tanggal_akhir')
                ->label('End Date'),

            ExportColumn::make('allocation_hours')
                ->label('Alokasi Jam'),

            ExportColumn::make('priority')
                ->label('Priority'),


        ];
    }


    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Export tasks selesai. File sudah siap diunduh.';
    }
}
