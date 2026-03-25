<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class ShowResources extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    private const RANGE_WEEKLY = 'weekly';
    private const RANGE_MONTHLY = 'monthly';
    private const DAILY_CAPACITY_HOURS = 8;

    public array $resourcesData = [];
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?array $formData = [];
    public ?string $range_type = 'monthly';
    public ?int $staff_id = null;
    public ?int $role_id = null;
    public ?int $project_id = null;
    public array $kpis = [];

    public ?string $selectedDate = null;
    public ?string $selectedStaffName = null;
    public array $overloadDetails = [];

    public function mount(): void
    {
        $this->setDefaultRange();
        $this->syncFormWithRange();
        $this->reloadResources();
    }

    public function render(): View
    {
        return view('livewire.show-resources');
    }

    public function reloadResources(): void
    {
        [$start, $end] = $this->resolveDateWindow();

        $tasks = $this->buildFilteredTasksQuery($start, $end)
            ->orderBy('staff_id')
            ->get();

        $dates = $this->buildDates();
        $resources = $this->buildResourceRows($tasks, $dates);

        $this->resourcesData = [
            'dates' => $dates,
            'rows' => $resources,
        ];

        $this->kpis = $this->buildKpis($tasks, $resources, $dates, $start, $end);
    }

    public function previousRange(): void
    {
        $this->shiftRange('sub');
    }

    public function nextRange(): void
    {
        $this->shiftRange('add');
    }

    public function setRange(string $type): void
    {
        if (!in_array($type, [self::RANGE_WEEKLY, self::RANGE_MONTHLY], true)) {
            return;
        }

        $this->range_type = $type;
        $this->applyCurrentRangePreset();
        $this->syncFormWithRange();
        $this->reloadResources();
    }

    public function resetFilters(): void
    {
        $this->staff_id = null;
        $this->role_id = null;
        $this->project_id = null;

        $this->syncFormWithRange();
        $this->reloadResources();
    }

    public function openOverloadDetails(int $staffId, string $date): void
    {
        $targetDate = Carbon::parse($date)->startOfDay();

        $tasks = Task::query()
            ->with('project')
            ->where('staff_id', $staffId)
            ->when($this->project_id, fn(Builder $query) => $query->where('project_id', $this->project_id))
            ->when($this->role_id, fn(Builder $query) => $query->whereHas('staff', fn(Builder $staff) => $staff->where('role_id', $this->role_id)))
            ->where(function (Builder $query) use ($targetDate) {
                $query
                    ->where(function (Builder $nested) use ($targetDate) {
                        $nested
                            ->where('is_long_term', false)
                            ->whereDate('tanggal', $targetDate->toDateString());
                    })
                    ->orWhere(function (Builder $nested) use ($targetDate) {
                        $nested
                            ->where('is_long_term', true)
                            ->whereDate('tanggal', '<=', $targetDate->toDateString())
                            ->whereDate('tanggal_akhir', '>=', $targetDate->toDateString());
                    });
            })
            ->get();

        $details = $tasks->map(function (Task $task) use ($targetDate) {
            $hours = 0;

            if ($task->is_long_term) {
                if (!$targetDate->isWeekend()) {
                    $hours = max(0, (int) ($task->allocation_hours ?? 0));
                }
            } elseif ($task->tanggal && Carbon::parse($task->tanggal)->isSameDay($targetDate)) {
                $hours = max(0, (int) ($task->estimasi_jam ?? 0));
            }

            return [
                'task_name' => $task->task_name,
                'project_name' => $task->project?->project_name ?? '-',
                'status' => $task->status,
                'priority' => $task->priority,
                'hours' => $hours,
            ];
        })
            ->filter(fn(array $item) => $item['hours'] > 0)
            ->sortByDesc('hours')
            ->values()
            ->all();

        $this->selectedDate = $targetDate->format('Y-m-d');
        $this->selectedStaffName = Staff::query()->whereKey($staffId)->value('name') ?? 'PIC';
        $this->overloadDetails = $details;
        $this->mountAction('viewOverloadDetails');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('start_date')
                    ->label('Start Date')
                    ->format('Y-m-d')
                    ->default(Carbon::now()->startOfMonth()->format('Y-m-d'))
                    ->required()
                    ->live()
                    ->disabled()
                    ->afterStateUpdated(fn($state) => $this->updateFilter('start_date', $state)),

                Forms\Components\DatePicker::make('end_date')
                    ->label('End Date')
                    ->format('Y-m-d')
                    ->default(Carbon::now()->endOfMonth()->format('Y-m-d'))
                    ->required()
                    ->live()
                    ->disabled()
                    ->afterStateUpdated(fn($state) => $this->updateFilter('end_date', $state)),

                Forms\Components\Select::make('staff_id')
                    ->label('PIC')
                    ->options(fn() => Staff::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->placeholder('All PIC')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn($state) => $this->updateNumericFilter('staff_id', $state)),

                Forms\Components\Select::make('role_id')
                    ->label('Jobdesk')
                    ->options(fn() => Role::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->placeholder('All Jobdesk')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn($state) => $this->updateNumericFilter('role_id', $state)),

                Forms\Components\Select::make('project_id')
                    ->label('Project')
                    ->options(fn() => Project::query()->orderBy('project_name')->pluck('project_name', 'id')->toArray())
                    ->placeholder('All Project')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn($state) => $this->updateNumericFilter('project_id', $state)),
            ])
            ->statePath('formData')
            ->columns(5);
    }

    protected function updateFilter(string $field, ?string $value): void
    {
        $this->{$field} = $value;
        $this->reloadResources();
    }

    protected function setDefaultRange(): void
    {
        $this->start_date ??= Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date ??= Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    protected function syncFormWithRange(): void
    {
        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'staff_id' => $this->staff_id,
            'role_id' => $this->role_id,
            'project_id' => $this->project_id,
        ]);
    }

    protected function updateNumericFilter(string $field, mixed $value): void
    {
        $this->{$field} = is_numeric($value) ? (int) $value : null;
        $this->reloadResources();
    }

    protected function resolveDateWindow(): array
    {
        return [
            Carbon::parse($this->start_date)->startOfDay(),
            Carbon::parse($this->end_date)->endOfDay(),
        ];
    }

    protected function buildFilteredTasksQuery(Carbon $start, Carbon $end): Builder
    {
        return Task::query()
            ->with(['staff', 'project'])
            ->where(fn(Builder $query) => $this->applyOverlappingDateRange($query, $start, $end))
            ->when($this->staff_id, fn(Builder $query) => $query->where('staff_id', $this->staff_id))
            ->when($this->role_id, fn(Builder $query) => $query->whereHas('staff', fn(Builder $staff) => $staff->where('role_id', $this->role_id)))
            ->when($this->project_id, fn(Builder $query) => $query->where('project_id', $this->project_id));
    }

    protected function applyOverlappingDateRange(Builder $query, Carbon $start, Carbon $end): void
    {
        $query
            ->whereBetween('tanggal', [$start, $end])
            ->orWhereBetween('tanggal_akhir', [$start, $end])
            ->orWhere(function (Builder $nested) use ($start, $end) {
                $nested
                    ->where('tanggal', '<', $start)
                    ->where('tanggal_akhir', '>', $end);
            });
    }

    protected function buildDates(): array
    {
        $dates = [];
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        for ($day = $start->copy(); $day->lt($end); $day->addDay()) {
            $dates[] = $day->format('Y-m-d');
        }

        return $dates;
    }

    protected function buildResourceRows(Collection $tasks, array $dates): array
    {
        $workingDays = collect($dates)
            ->filter(fn(string $date) => !Carbon::parse($date)->isWeekend())
            ->count();

        $capacityHours = $workingDays * self::DAILY_CAPACITY_HOURS;
        $rows = [];

        foreach ($tasks->groupBy('staff_id') as $staffId => $staffTasks) {
            $staff = $staffTasks->first()->staff;
            $row = [
                'staff_id' => (int) $staffId,
                'name' => $staff?->name ?? '-',
                'demand_hours' => 0,
                'capacity_hours' => $capacityHours,
                'utilization_pct' => 0,
                'days' => array_fill_keys($dates, 0),
            ];

            foreach ($staffTasks as $task) {
                if ($task->is_long_term && $task->tanggal && $task->tanggal_akhir) {
                    $this->applyLongTermTaskLoad($row, $task);
                } else {
                    $this->applySingleDayTaskLoad($row, $task);
                }
            }

            $row['utilization_pct'] = $row['capacity_hours'] > 0
                ? round(($row['demand_hours'] / $row['capacity_hours']) * 100, 1)
                : 0;

            $rows[] = $row;
        }

        return $rows;
    }

    protected function applyLongTermTaskLoad(array &$row, Task $task): void
    {
        $start = Carbon::parse($task->tanggal);
        $end = Carbon::parse($task->tanggal_akhir);

        for ($day = $start->copy(); $day->lt($end); $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            $date = $day->format('Y-m-d');
            if (!isset($row['days'][$date])) {
                continue;
            }

            $hours = max(0, (int) ($task->allocation_hours ?? 0));
            $row['days'][$date] += $hours;
            $row['demand_hours'] += $hours;
        }
    }

    protected function applySingleDayTaskLoad(array &$row, Task $task): void
    {
        $taskDate = Carbon::parse($task->tanggal);
        if ($taskDate->isWeekend()) {
            return;
        }

        $date = $taskDate->format('Y-m-d');
        if (!isset($row['days'][$date])) {
            return;
        }

        $hours = max(0, (int) ($task->estimasi_jam ?? 0));
        $row['days'][$date] += $hours;
        $row['demand_hours'] += $hours;
    }

    protected function buildKpis(Collection $tasks, array $resources, array $dates, Carbon $start, Carbon $end): array
    {
        $overloadCells = 0;
        $totalDemand = 0;
        $totalCapacity = 0;

        foreach ($resources as $row) {
            $totalDemand += (int) ($row['demand_hours'] ?? 0);
            $totalCapacity += (int) ($row['capacity_hours'] ?? 0);

            foreach ($row['days'] as $hours) {
                if ((int) $hours > self::DAILY_CAPACITY_HOURS) {
                    $overloadCells++;
                }
            }
        }

        $currentMetrics = [
            'total_tasks' => $tasks->count(),
            'demand_hours' => $totalDemand,
            'overload_cells' => $overloadCells,
        ];

        $trend = $this->buildTrendMetrics($currentMetrics, count($dates), $start, $end);

        return [
            'total_tasks' => $tasks->count(),
            'active_pic' => count($resources),
            'demand_hours' => $totalDemand,
            'capacity_hours' => $totalCapacity,
            'utilization_pct' => $totalCapacity > 0 ? round(($totalDemand / $totalCapacity) * 100, 1) : 0,
            'overload_cells' => $overloadCells,
            'trend' => $trend,
        ];
    }

    protected function buildTrendMetrics(array $currentMetrics, int $displayedDays, Carbon $start, Carbon $end): array
    {
        $rangeDays = max(1, $displayedDays);
        $previousStart = $start->copy()->subDays($rangeDays);
        $previousEnd = $start->copy()->subDay()->endOfDay();

        $previousTasks = $this->buildFilteredTasksQuery($previousStart, $previousEnd)->get();

        $previousDates = [];
        for ($day = $previousStart->copy(); $day->lt($start); $day->addDay()) {
            $previousDates[] = $day->format('Y-m-d');
        }

        $previousRows = $this->buildResourceRows($previousTasks, $previousDates);

        $previousOverload = 0;
        $previousDemand = 0;
        foreach ($previousRows as $row) {
            $previousDemand += (int) $row['demand_hours'];
            foreach ($row['days'] as $hours) {
                if ((int) $hours > self::DAILY_CAPACITY_HOURS) {
                    $previousOverload++;
                }
            }
        }

        return [
            'tasks_delta_pct' => $this->calculateDeltaPercent($currentMetrics['total_tasks'], $previousTasks->count()),
            'demand_delta_pct' => $this->calculateDeltaPercent($currentMetrics['demand_hours'], $previousDemand),
            'overload_delta_pct' => $this->calculateDeltaPercent($currentMetrics['overload_cells'], $previousOverload),
        ];
    }

    protected function calculateDeltaPercent(int|float $current, int|float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function applyCurrentRangePreset(): void
    {
        $now = Carbon::now();

        if ($this->range_type === self::RANGE_WEEKLY) {
            $this->start_date = $now->copy()->startOfWeek()->format('Y-m-d');
            $this->end_date = $now->copy()->endOfWeek()->format('Y-m-d');
            return;
        }

        $this->start_date = $now->copy()->startOfMonth()->format('Y-m-d');
        $this->end_date = $now->copy()->endOfMonth()->format('Y-m-d');
    }

    protected function shiftRange(string $direction): void
    {
        if (!in_array($direction, ['add', 'sub'], true)) {
            return;
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        if ($this->range_type === self::RANGE_WEEKLY) {
            $start = $direction === 'add' ? $start->addWeek() : $start->subWeek();
            $end = $direction === 'add' ? $end->addWeek() : $end->subWeek();
            $this->start_date = $start->startOfWeek()->format('Y-m-d');
            $this->end_date = $end->endOfWeek()->format('Y-m-d');
        } else {
            $start = $direction === 'add' ? $start->addMonth() : $start->subMonth();
            $end = $direction === 'add' ? $end->addMonth() : $end->subMonth();
            $this->start_date = $start->startOfMonth()->format('Y-m-d');
            $this->end_date = $end->endOfMonth()->format('Y-m-d');
        }

        $this->syncFormWithRange();
        $this->reloadResources();
    }

    public function viewOverloadDetailsAction(): Action
    {
        return Action::make('viewOverloadDetails')
            ->closeModalByClickingAway(true)
            ->closeModalByEscaping(true)
            ->modalHeading('Detail Overload')
            ->modalDescription(function (): string {
                if (!$this->selectedStaffName || !$this->selectedDate) {
                    return 'Informasi overload tidak tersedia.';
                }

                return "{$this->selectedStaffName} - {$this->selectedDate}";
            })
            ->modalContent(fn() => view('livewire.partials.overload-details-table', [
                'overloadDetails' => $this->overloadDetails,
            ]))
            ->modalWidth(MaxWidth::FourExtraLarge)
            ->extraModalWindowAttributes([
                'x-on:keydown.escape.window' => '$wire.unmountAction(false, true)',
                'x-on:click.outside' => '$wire.unmountAction(false, true)',
            ], merge: true)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
    }
}
