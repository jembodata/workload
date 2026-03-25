<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class ShowGantt extends Component implements HasForms
{
    use InteractsWithForms;

    private const RANGE_WEEKLY = 'weekly';
    private const RANGE_MONTHLY = 'monthly';
    private const DEFAULT_DAILY_CAPACITY = 8;

    public array $ganttData = ['data' => [], 'links' => []];
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?array $formData = [];
    public ?string $range_type = 'monthly';
    public array $priorityFilter = ['urgent', 'high', 'medium', 'low', 'not_priority'];
    public ?int $staff_id = null;
    public ?int $role_id = null;
    public ?int $project_id = null;
    public array $kpis = [];

    public function mount(): void
    {
        $this->setDefaultRange();
        $this->syncFormWithRange();
        $this->reloadGantt();
    }

    public function render(): View
    {
        return view('livewire.show-gantt');
    }

    public function reloadGantt(): void
    {
        [$start, $end] = $this->resolveDateWindow();

        $tasks = Task::query()
            ->with(['staff', 'project'])
            ->where(fn($query) => $this->applyOverlappingDateRange($query, $start, $end))
            ->when($this->staff_id, fn($query) => $query->where('staff_id', $this->staff_id))
            ->when($this->role_id, fn($query) => $query->whereHas('staff', fn($staff) => $staff->where('role_id', $this->role_id)))
            ->when($this->project_id, fn($query) => $query->where('project_id', $this->project_id))
            ->orderBy('staff_id')
            ->orderBy('tanggal')
            ->get();

        $this->kpis = [
            'total_tasks' => $tasks->count(),
            'active_pic' => $tasks->pluck('staff_id')->filter()->unique()->count(),
            'overdue_tasks' => $tasks->where('status', 'overdue')->count(),
            'planned_hours' => $this->calculatePlannedHours($tasks, $start, $end),
        ];

        $this->ganttData = [
            'data' => $this->buildGanttData($tasks->groupBy('staff_id')),
            'links' => [],
        ];

        $this->dispatch(
            'refresh-gantt',
            ganttData: $this->ganttData,
            startDate: $this->start_date,
            endDate: $this->end_date
        );
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
        $this->reloadGantt();
    }

    public function resetFilters(): void
    {
        $this->staff_id = null;
        $this->role_id = null;
        $this->project_id = null;
        $this->priorityFilter = ['urgent', 'high', 'medium', 'low', 'not_priority'];

        $this->syncFormWithRange();
        $this->reloadGantt();
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
        $this->reloadGantt();
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
        $this->reloadGantt();
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
        $this->reloadGantt();
    }

    protected function resolveDateWindow(): array
    {
        return [
            Carbon::parse($this->start_date)->startOfDay(),
            Carbon::parse($this->end_date)->endOfDay(),
        ];
    }

    protected function applyOverlappingDateRange($query, Carbon $start, Carbon $end): void
    {
        $query
            ->whereBetween('tanggal', [$start, $end])
            ->orWhereBetween('tanggal_akhir', [$start, $end])
            ->orWhere(function ($nested) use ($start, $end) {
                $nested
                    ->where('tanggal', '<', $start)
                    ->where('tanggal_akhir', '>', $end);
            });
    }

    protected function buildDateRange(): array
    {
        $dates = [];
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $dates[] = $day->format('Y-m-d');
        }

        return $dates;
    }

    protected function initializeDailyCapacity(array $dates): array
    {
        $capacity = [];
        foreach ($dates as $date) {
            $capacity[$date] = Carbon::parse($date)->isWeekend() ? 0 : self::DEFAULT_DAILY_CAPACITY;
        }

        return $capacity;
    }

    protected function applyLongTermAllocation(array &$capacity, Task $task): void
    {
        $start = Carbon::parse($task->tanggal);
        $end = Carbon::parse($task->tanggal_akhir);

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            $date = $day->format('Y-m-d');
            if (!isset($capacity[$date])) {
                continue;
            }

            $allocation = max(0, (int) ($task->allocation_hours ?? 0));
            $capacity[$date] = max(0, $capacity[$date] - $allocation);
        }
    }

    protected function scheduleTask(array &$capacity, array $dates, Task $task): int
    {
        $remaining = (int) ($task->estimasi_jam ?? 0);
        $startDate = Carbon::parse($task->tanggal)->format('Y-m-d');
        $dayIndex = array_search($startDate, $dates, true);
        $dateCount = count($dates);

        $daysUsed = 0;
        while ($remaining > 0 && $dayIndex !== false && $dayIndex < $dateCount) {
            $date = $dates[$dayIndex];
            if (Carbon::parse($date)->isWeekend()) {
                $dayIndex++;
                continue;
            }

            $available = $capacity[$date] ?? 0;
            if ($available > 0) {
                $consumed = min($available, $remaining);
                $capacity[$date] -= $consumed;
                $remaining -= $consumed;
                $daysUsed++;
            }

            $dayIndex++;
        }

        return max(1, $daysUsed);
    }

    protected function buildGanttData(Collection $grouped): array
    {
        $dates = $this->buildDateRange();
        $rows = [];

        foreach ($grouped as $staffId => $tasks) {
            $staff = $tasks->first()->staff;

            $rows[] = [
                'id' => "staff_{$staffId}",
                'text' => $staff->name,
                'type' => 'project',
                'open' => true,
                'color' => $staff->color,
            ];

            $capacity = $this->initializeDailyCapacity($dates);

            foreach ($tasks as $task) {
                if ($task->is_long_term && $task->tanggal && $task->tanggal_akhir) {
                    $this->applyLongTermAllocation($capacity, $task);
                }
            }

            foreach ($tasks as $task) {
                $startDate = Carbon::parse($task->tanggal)->format('Y-m-d');
                $duration = $task->is_long_term && $task->tanggal && $task->tanggal_akhir
                    ? $this->countWorkingDays($task->tanggal, $task->tanggal_akhir)
                    : $this->scheduleTask($capacity, $dates, $task);

                $rows[] = [
                    'id' => $task->id,
                    'parent' => "staff_{$staffId}",
                    'text' => $task->input,
                    'start_date' => $startDate,
                    'duration' => $duration,
                    'progress' => $this->resolveTaskProgress($task),
                    'staff_id' => $staffId,
                    'staff_name' => $staff->name,
                    'color' => $staff->color,
                    'open' => true,
                    'priority' => $task->priority,
                ];
            }
        }

        return $rows;
    }

    protected function countWorkingDays(string $start, string $end): int
    {
        $count = 0;
        for ($day = Carbon::parse($start)->copy(); $day->lte(Carbon::parse($end)); $day->addDay()) {
            if (!$day->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }

    protected function resolveTaskProgress(Task $task): float
    {
        if (is_numeric($task->progress)) {
            $percent = max(0, min(100, (int) $task->progress));
            return $percent / 100;
        }

        return match ($task->status) {
            'progress' => 0.5,
            'closed' => 1.0,
            default => 0.0,
        };
    }

    protected function calculatePlannedHours(Collection $tasks, Carbon $start, Carbon $end): int
    {
        return (int) $tasks->sum(function (Task $task) use ($start, $end) {
            if ($task->is_long_term && $task->tanggal && $task->tanggal_akhir) {
                $workingDays = $this->countWorkingDaysInRange(
                    Carbon::parse($task->tanggal),
                    Carbon::parse($task->tanggal_akhir),
                    $start,
                    $end
                );

                return $workingDays * max(0, (int) ($task->allocation_hours ?? 0));
            }

            return max(0, (int) ($task->estimasi_jam ?? 0));
        });
    }

    protected function countWorkingDaysInRange(Carbon $taskStart, Carbon $taskEnd, Carbon $windowStart, Carbon $windowEnd): int
    {
        $start = $taskStart->copy()->startOfDay()->max($windowStart->copy()->startOfDay());
        $end = $taskEnd->copy()->endOfDay()->min($windowEnd->copy()->endOfDay());

        if ($start->gt($end)) {
            return 0;
        }

        $count = 0;
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (!$day->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }
}
