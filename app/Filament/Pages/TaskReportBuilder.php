<?php

namespace App\Filament\Pages;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Staff;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TaskReportBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Create Report';
    protected static ?string $slug = 'task-report-builder';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.task-report-builder';

    public ?array $formData = [];

    // Task picker state
    public string $taskSearch = '';
    public ?int $taskFilterStaffId = null;
    public ?int $taskFilterProjectId = null;
    public string $taskFilterStatus = '';
    public bool $showOnlySelectedTasks = false;
    public int $taskPickerPage = 1;
    public int $taskPickerPerPage = 25;

    // Issue picker state
    public string $issueSearch = '';
    public string $issueFilterStatus = '';
    public string $issueFilterPriority = '';
    public ?int $issueFilterStaffId = null;
    public bool $showOnlySelectedIssues = false;
    public int $issuePickerPage = 1;
    public int $issuePickerPerPage = 20;

    /** @var array<string> */
    public array $selectedTaskIds = [];

    /** @var array<string> */
    public array $selectedIssueIds = [];

    public function mount(): void
    {
        $this->form->fill([
            'title_id' => 'isi JUDUL (ID)',
            'title_en' => 'isi TITLE (EN)',
            'meeting_present' => [],
            'meeting_absent' => [],
            'meeting_day' => now()->toDateString(),
            'meeting_time' => '08.00 - 10.00',
            'meeting_place' => '',
            'document_no' => '',
            'effective_date' => now()->toDateString(),
            'revision' => '0',
        ]);
    }

    public function renderPdfAction(): Action
    {
        return Action::make('renderPdf')
            ->label('Render PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('primary')
            ->form([
                Forms\Components\Select::make('orientation')
                    ->label('Orientation')
                    ->options([
                        'portrait' => 'Portrait',
                        'landscape' => 'Landscape',
                    ])
                    ->default('portrait')
                    ->native(false)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->renderPdf((string) ($data['orientation'] ?? 'portrait'));
            });
    }

    public function renderPdf(string $orientation = 'portrait'): void
    {
        $orientation = in_array($orientation, ['portrait', 'landscape'], true) ? $orientation : 'portrait';

        $data = array_merge($this->getReportData(), [
            'logoSrc' => public_path('images/logo_report.png'),
            'orientation' => $orientation,
        ]);

        $token = (string) Str::uuid();
        Cache::put("task-report-pdf:{$token}", $data, now()->addMinutes(10));

        $url = route('task-report.preview', ['token' => $token]);
        $this->js("window.open('{$url}', '_blank')");
    }

    protected function getViewData(): array
    {
        $taskPickerData = $this->getTaskPickerData();
        $issuePickerData = $this->getIssuePickerData();

        return array_merge([
            'availableTasks' => $taskPickerData['items'],
            'taskPickerMeta' => $taskPickerData['meta'],
            'staffFilterOptions' => Staff::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            'projectFilterOptions' => Project::query()->orderBy('project_name')->pluck('project_name', 'id')->toArray(),
            'statusFilterOptions' => Task::query()->select('status')->distinct()->orderBy('status')->pluck('status')->all(),

            'availableIssues' => $issuePickerData['items'],
            'issuePickerMeta' => $issuePickerData['meta'],
            'issueStatusOptions' => Issue::query()->select('status')->distinct()->orderBy('status')->pluck('status')->all(),
            'issuePriorityOptions' => Issue::query()->select('priority')->distinct()->orderBy('priority')->pluck('priority')->all(),
            'issueStaffOptions' => Staff::query()->orderBy('name')->pluck('name', 'id')->toArray(),
        ], $this->getReportData());
    }

    // region: update hooks
    public function updatedSelectedTaskIds(): void
    {
        $this->selectedTaskIds = collect($this->selectedTaskIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values()
            ->all();

        // Keep selected issues only from selected tasks
        $selectedTaskIds = $this->getSelectedTaskIdsAsInt();
        if (empty($selectedTaskIds)) {
            $this->selectedIssueIds = [];
            $this->issuePickerPage = 1;
            return;
        }

        $allowedIssueIds = Issue::query()
            ->whereIn('task_id', $selectedTaskIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $allowedMap = array_fill_keys($allowedIssueIds, true);
        $this->selectedIssueIds = array_values(array_filter(
            $this->selectedIssueIds,
            fn($id) => isset($allowedMap[(string) $id])
        ));

        $this->issuePickerPage = 1;
    }

    public function updatedSelectedIssueIds(): void
    {
        $this->selectedIssueIds = collect($this->selectedIssueIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function updatedTaskSearch(): void { $this->taskPickerPage = 1; }
    public function updatedTaskFilterStaffId(): void { $this->taskPickerPage = 1; }
    public function updatedTaskFilterProjectId(): void { $this->taskPickerPage = 1; }
    public function updatedTaskFilterStatus(): void { $this->taskPickerPage = 1; }
    public function updatedShowOnlySelectedTasks(): void { $this->taskPickerPage = 1; }

    public function updatedIssueSearch(): void { $this->issuePickerPage = 1; }
    public function updatedIssueFilterStatus(): void { $this->issuePickerPage = 1; }
    public function updatedIssueFilterPriority(): void { $this->issuePickerPage = 1; }
    public function updatedIssueFilterStaffId(): void { $this->issuePickerPage = 1; }
    public function updatedShowOnlySelectedIssues(): void { $this->issuePickerPage = 1; }
    // endregion

    // region: task picker actions
    public function nextTaskPickerPage(): void
    {
        if ($this->taskPickerPage < $this->resolveTaskPickerLastPage()) {
            $this->taskPickerPage++;
        }
    }

    public function previousTaskPickerPage(): void
    {
        if ($this->taskPickerPage > 1) {
            $this->taskPickerPage--;
        }
    }

    public function selectCurrentPageTasks(): void
    {
        $ids = $this->getTaskPickerData()['items']->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->selectedTaskIds = array_values(array_unique([...$this->selectedTaskIds, ...$ids]));
    }

    public function selectFilteredTasks(): void
    {
        $ids = (clone $this->buildTaskPickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedTaskIds = array_values(array_unique([...$this->selectedTaskIds, ...$ids]));
    }

    public function unselectFilteredTasks(): void
    {
        $filteredIds = (clone $this->buildTaskPickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $filteredMap = array_fill_keys($filteredIds, true);

        $this->selectedTaskIds = array_values(array_filter(
            $this->selectedTaskIds,
            fn($id) => !isset($filteredMap[(string) $id])
        ));
    }

    public function clearSelectedTasks(): void
    {
        $this->selectedTaskIds = [];
        $this->selectedIssueIds = [];
    }
    // endregion

    // region: issue picker actions
    public function nextIssuePickerPage(): void
    {
        if ($this->issuePickerPage < $this->resolveIssuePickerLastPage()) {
            $this->issuePickerPage++;
        }
    }

    public function previousIssuePickerPage(): void
    {
        if ($this->issuePickerPage > 1) {
            $this->issuePickerPage--;
        }
    }

    public function selectCurrentPageIssues(): void
    {
        $ids = $this->getIssuePickerData()['items']->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->selectedIssueIds = array_values(array_unique([...$this->selectedIssueIds, ...$ids]));
    }

    public function selectFilteredIssues(): void
    {
        $ids = (clone $this->buildIssuePickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedIssueIds = array_values(array_unique([...$this->selectedIssueIds, ...$ids]));
    }

    public function unselectFilteredIssues(): void
    {
        $filteredIds = (clone $this->buildIssuePickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $filteredMap = array_fill_keys($filteredIds, true);

        $this->selectedIssueIds = array_values(array_filter(
            $this->selectedIssueIds,
            fn($id) => !isset($filteredMap[(string) $id])
        ));
    }

    public function clearSelectedIssues(): void
    {
        $this->selectedIssueIds = [];
    }
    // endregion

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Form Report')
                    ->description('Isi data meeting untuk ditampilkan di preview.')
                    ->schema([
                        Forms\Components\TextInput::make('title_id')
                            ->label('Judul (ID)')
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('title_en')
                            ->label('Judul (EN)')
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('meeting_present')
                            ->label('Hadir')
                            ->options(fn(Get $get) => $this->getStaffOptionsExcluding($get('meeting_absent')))
                            ->searchable()
                            ->preload()
                            ->multiple()
                            ->native(false)
                            ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                                $presentIds = collect(Arr::wrap($state))
                                    ->map(fn($id) => (int) $id)
                                    ->filter()
                                    ->values();

                                $filteredAbsent = collect(Arr::wrap($get('meeting_absent')))
                                    ->map(fn($id) => (int) $id)
                                    ->reject(fn($id) => $presentIds->contains($id))
                                    ->values()
                                    ->all();

                                $set('meeting_absent', $filteredAbsent);
                            })
                            ->live(),
                        Forms\Components\Select::make('meeting_absent')
                            ->label('Absen')
                            ->options(fn(Get $get) => $this->getStaffOptionsExcluding($get('meeting_present')))
                            ->searchable()
                            ->preload()
                            ->multiple()
                            ->native(false)
                            ->live(),
                        Forms\Components\DatePicker::make('meeting_day')
                            ->label('Hari')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->live(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('meeting_time')
                                    ->label('Waktu')
                                    ->live(),
                                Forms\Components\TextInput::make('meeting_place')
                                    ->label('Tempat')
                                    ->live(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('document_no')
                                    ->label('No. Document')
                                    ->live(),
                                Forms\Components\DatePicker::make('effective_date')
                                    ->label('Effective Date')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->live(),
                                Forms\Components\TextInput::make('revision')
                                    ->label('Revision')
                                    ->live(),
                            ]),
                    ]),
            ])
            ->statePath('formData');
    }

    protected function estimateTotalPages(array $rows): int
    {
        if (empty($rows)) {
            return 1;
        }

        $availableUnitsPerPage = 42;
        $usedUnits = 0;

        foreach ($rows as $row) {
            $output = (string) ($row['output'] ?? '');
            $input = (string) ($row['input'] ?? '');

            $lineUnits = max(
                1,
                (int) ceil(mb_strlen($output) / 110),
                (int) ceil(mb_strlen($input) / 70),
            );

            $usedUnits += 1 + $lineUnits;
        }

        return max(1, (int) ceil($usedUnits / $availableUnitsPerPage));
    }

    protected function getReportData(): array
    {
        $previewRows = $this->getPreviewRows();
        $totalPages = $this->estimateTotalPages($previewRows);

        return [
            'previewRows' => $previewRows,
            'titleId' => (string) data_get($this->formData, 'title_id', ''),
            'titleEn' => (string) data_get($this->formData, 'title_en', ''),
            'meetingPresent' => $this->formatParticipants(data_get($this->formData, 'meeting_present')),
            'meetingAbsent' => $this->formatParticipants(data_get($this->formData, 'meeting_absent')),
            'meetingDay' => $this->formatMeetingDay(data_get($this->formData, 'meeting_day')),
            'meetingTime' => (string) data_get($this->formData, 'meeting_time', ''),
            'meetingPlace' => (string) data_get($this->formData, 'meeting_place', ''),
            'documentNo' => (string) data_get($this->formData, 'document_no', ''),
            'effectiveDate' => $this->formatIndonesianDate(data_get($this->formData, 'effective_date')),
            'revision' => (string) data_get($this->formData, 'revision', ''),
            'pageLabel' => "1 dari {$totalPages}",
            'logoSrc' => asset('images/logo_report.png'),
        ];
    }

    protected function formatMeetingDay(mixed $value): string
    {
        return $this->formatIndonesianDate($value, withDayName: true);
    }

    protected function formatIndonesianDate(mixed $value, bool $withDayName = false): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return Carbon::parse((string) $value)
                ->locale('id')
                ->translatedFormat($withDayName ? 'l, d F Y' : 'd F Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected function formatParticipants(mixed $value): string
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (blank($value)) {
            return '';
        }

        if (!is_array($value)) {
            return trim((string) $value);
        }

        $orderedIds = collect($value)
            ->map(fn($id) => is_numeric($id) ? (int) $id : null)
            ->filter()
            ->values()
            ->all();

        if (empty($orderedIds)) {
            return collect($value)
                ->map(fn($item) => is_scalar($item) ? trim((string) $item) : '')
                ->filter()
                ->implode(', ');
        }

        $namesById = Staff::query()->whereIn('id', $orderedIds)->pluck('name', 'id');

        return collect($orderedIds)
            ->map(fn($id) => $namesById->get($id))
            ->filter()
            ->implode(', ');
    }

    protected function buildTaskPickerQuery(): Builder
    {
        return Task::query()
            ->with(['staff', 'project'])
            ->when($this->showOnlySelectedTasks, fn(Builder $query) => $query->whereIn('id', $this->getSelectedTaskIdsAsInt()))
            ->when($this->taskFilterStaffId, fn(Builder $query) => $query->where('staff_id', $this->taskFilterStaffId))
            ->when($this->taskFilterProjectId, fn(Builder $query) => $query->where('project_id', $this->taskFilterProjectId))
            ->when($this->taskFilterStatus !== '', fn(Builder $query) => $query->where('status', $this->taskFilterStatus))
            ->when($this->taskSearch !== '', function (Builder $query) {
                $term = '%' . $this->taskSearch . '%';

                $query->where(function (Builder $sub) use ($term) {
                    $sub->where('task_name', 'like', $term)
                        ->orWhere('input', 'like', $term)
                        ->orWhere('output', 'like', $term)
                        ->orWhereHas('staff', fn(Builder $staff) => $staff->where('name', 'like', $term));
                });
            })
            ->latest();
    }

    protected function buildIssuePickerQuery(): Builder
    {
        $selectedTaskIds = $this->getSelectedTaskIdsAsInt();

        return Issue::query()
            ->with(['task', 'staff'])
            ->whereIn('task_id', empty($selectedTaskIds) ? [-1] : $selectedTaskIds)
            ->when($this->showOnlySelectedIssues, fn(Builder $query) => $query->whereIn('id', collect($this->selectedIssueIds)->map(fn($id) => (int) $id)->all()))
            ->when($this->issueFilterStatus !== '', fn(Builder $query) => $query->where('status', $this->issueFilterStatus))
            ->when($this->issueFilterPriority !== '', fn(Builder $query) => $query->where('priority', $this->issueFilterPriority))
            ->when($this->issueFilterStaffId, fn(Builder $query) => $query->where('staff_id', $this->issueFilterStaffId))
            ->when($this->issueSearch !== '', function (Builder $query) {
                $term = '%' . $this->issueSearch . '%';

                $query->where(function (Builder $sub) use ($term) {
                    $sub->where('issue_name', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('task', fn(Builder $task) => $task->where('task_name', 'like', $term))
                        ->orWhereHas('staff', fn(Builder $staff) => $staff->where('name', 'like', $term));
                });
            })
            ->latest();
    }

    protected function resolveTaskPickerLastPage(): int
    {
        $total = (clone $this->buildTaskPickerQuery())->count();

        return max(1, (int) ceil($total / $this->taskPickerPerPage));
    }

    protected function resolveIssuePickerLastPage(): int
    {
        $total = (clone $this->buildIssuePickerQuery())->count();

        return max(1, (int) ceil($total / $this->issuePickerPerPage));
    }

    protected function getTaskPickerData(): array
    {
        $query = $this->buildTaskPickerQuery();
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $this->taskPickerPerPage));

        $page = max(1, min($this->taskPickerPage, $lastPage));
        $this->taskPickerPage = $page;

        $items = (clone $query)
            ->forPage($page, $this->taskPickerPerPage)
            ->get();

        $from = $total === 0 ? 0 : (($page - 1) * $this->taskPickerPerPage) + 1;
        $to = min($total, $page * $this->taskPickerPerPage);

        return [
            'items' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    protected function getIssuePickerData(): array
    {
        $query = $this->buildIssuePickerQuery();
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $this->issuePickerPerPage));

        $page = max(1, min($this->issuePickerPage, $lastPage));
        $this->issuePickerPage = $page;

        $items = (clone $query)
            ->forPage($page, $this->issuePickerPerPage)
            ->get();

        $from = $total === 0 ? 0 : (($page - 1) * $this->issuePickerPerPage) + 1;
        $to = min($total, $page * $this->issuePickerPerPage);

        return [
            'items' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    protected function getPreviewRows(): array
    {
        $taskIds = $this->getSelectedTaskIdsAsInt();
        if (empty($taskIds)) {
            return [];
        }

        $selectedIssueIds = collect($this->selectedIssueIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $issuesByTask = Issue::query()
            ->whereIn('id', empty($selectedIssueIds) ? [-1] : $selectedIssueIds)
            ->get(['id', 'task_id', 'issue_name', 'description', 'status'])
            ->groupBy('task_id');

        $tasks = Task::query()
            ->with(['staff', 'project'])
            ->whereIn('id', $taskIds)
            ->get()
            ->keyBy('id');

        return collect($taskIds)
            ->map(fn(int $id) => $tasks->get($id))
            ->filter()
            ->values()
            ->map(function (Task $task, int $index) use ($issuesByTask) {
                $output = (string) ($task->output ?? '-');
                $selectedIssues = $issuesByTask->get($task->id, collect());

                if ($selectedIssues->isNotEmpty()) {
                    $issueText = $selectedIssues->map(function (Issue $issue): string {
                        $name = trim((string) $issue->issue_name);
                        $status = trim((string) ($issue->status ?? ''));
                        $desc = html_entity_decode((string) ($issue->description ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $desc = str_replace("\u{00A0}", ' ', $desc);
                        $desc = trim(strip_tags($desc));
                        $desc = preg_replace('/\s+/', ' ', $desc) ?? $desc;

                        $line = "- {$name}";
                        if ($status !== '') {
                            $line .= " ({$status})";
                        }
                        if ($desc !== '') {
                            $line .= ': ' . $desc;
                        }

                        return $line;
                    })->implode("\n");

                    $output = trim($output . "\n\n" . $issueText);
                }

                return [
                    'no' => $index + 1,
                    'item' => (string) ($task->task_name ?? '-'),
                    'input' => (string) ($task->input ?? '-'),
                    'output' => $output,
                    'target' => $task->tanggal ? $this->formatIndonesianDate($task->tanggal) : '-',
                    'pic' => (string) ($task->staff?->name ?? '-'),
                    'evaluasi' => ucfirst(str_replace('_', ' ', (string) ($task->status ?? '-'))),
                ];
            })
            ->all();
    }

    /** @return array<int> */
    protected function getSelectedTaskIdsAsInt(): array
    {
        return collect($this->selectedTaskIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function getStaffOptionsExcluding(mixed $excludedIds): array
    {
        $exclude = collect(Arr::wrap($excludedIds))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Staff::query()
            ->when(!empty($exclude), fn(Builder $query) => $query->whereNotIn('id', $exclude))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
