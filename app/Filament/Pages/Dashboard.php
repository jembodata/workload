<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PriorityTaskTable;
use App\Filament\Widgets\RoleOverview;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TaskChart;
use App\Filament\Widgets\StaffTaskChart;
use App\Filament\Widgets\WorkloadAlertToday;
use App\Filament\Widgets\DueThisWeek;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;
    public function mount(): void
    {
        $this->filters = $this->getFiltersFormDefaultValues();
    }

    public function getHeading(): string
    {
        return 'Workload & Project Management';
    }


    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            WorkloadAlertToday::class,
            DueThisWeek::class,
            PriorityTaskTable::class,
            StaffTaskChart::class,
            TaskChart::class,
            RoleOverview::class,
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('startDate')
                ->label('Tanggal Mulai')
                ->native(false)
                ->format('Y-m-d'),

            DatePicker::make('endDate')
                ->label('Tanggal Selesai')
                ->native(false)
                ->format('Y-m-d'),
        ]);
    }

    protected function getFiltersFormDefaultValues(): array
    {
        return [
            'startDate' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'endDate'   => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ];
    }
}
