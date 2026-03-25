<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Exports\TasksExporter;
use App\Filament\Pages\TaskReportBuilder;
use App\Filament\Resources\TaskResource;
use App\Imports\ImportTask;
use Filament\Actions;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
                ->slideOver()
                ->modalWidth(MaxWidth::Medium)
                ->icon('heroicon-o-plus')
                ->closeModalByClickingAway(false),
            Actions\Action::make('createReport')
                ->label('Create Report')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(TaskReportBuilder::getUrl()),
            Actions\ExportAction::make()
                ->exporter(TasksExporter::class)
                ->label('Export Tasks')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->slideOver()
                ->modalWidth(MaxWidth::Medium)
                ->formats([
                    ExportFormat::Xlsx,
                ]),
            // ExcelImportPlugin::make()
            \EightyNine\ExcelImport\ExcelImportAction::make()
                // ->use(\App\Imports\ImportTask::class    )
                ->label('Import Tasks')
                ->color('primary')
                ->slideOver()
                ->modalWidth(MaxWidth::Medium)
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->sampleExcel(
                    sampleData: [
                        [
                            'task_name' => 'helpdesk',
                            'input' => 'masukan input',
                            'output' => 'masukan output',
                            'tanggal' => '2025-12-23',
                            'estimasi_jam' => '1',
                            'staff_id' => '1',
                            'status' => 'progress',
                        ],

                        [
                            'task_name' => 'support',
                            'input' => 'cek sistem',
                            'output' => 'hasil cek',
                            'tanggal' => '2025-12-22',
                            'estimasi_jam' => '2',
                            'staff_id' => '2',
                            'status' => 'todo'
                        ],

                    ],
                    fileName: 'sample.xlsx',
                    sampleButtonLabel: 'Download Sample',
                    customiseActionUsing: fn(Action $action) => $action->color('secondary')
                        ->icon('heroicon-m-clipboard')
                )
        ];
    }

    private function mapStatusLabel(string $status): string
    {
        return match ($status) {
            'todo'      => 'Opened',
            'progress'  => 'Progress',
            'closed'      => 'Closed',
            'overdue'   => 'Overdue',
            'postponed' => 'Postponed',
            default     => ucfirst($status),
        };
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All')
                ->badge($this->getModel()::count()),
        ];

        $statuses = $this->getModel()::query()
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        foreach ($statuses as $status) {

            $slug = str($status)->slug()->toString();
            $label = $this->mapStatusLabel($status);

            $tabs[$slug] = Tab::make($label)
                ->badge(
                    $this->getModel()::where('status', $status)->count()
                )
                ->modifyQueryUsing(
                    fn($query) => $query->where('status', $status)
                );
        }

        return $tabs;
    }
}
