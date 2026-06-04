<x-filament-panels::page class="task-report-builder-page">
    <x-filament-actions::modals />

    @php
        $collapsed = $collapsedBuilderSteps ?? [];
        $isStep1Open = !($collapsed['1'] ?? false);
        $isStep2Open = !($collapsed['2'] ?? false);
        $isStep3Open = !($collapsed['3'] ?? false);
        $isStep4Open = !($collapsed['4'] ?? false);
        $isStep5Open = !($collapsed['5'] ?? false);
        $zoomClass = 'preview-zoom-' . str_replace('.', '-', $previewZoom ?? 'fit');
    @endphp
    <div id="report-non-desktop-notice">
        Preview report paling akurat untuk layar desktop (>= 1280px). Di layar kecil, gunakan tombol <strong>Render PDF</strong> untuk hasil final ukuran A4.
    </div>

    <div id="report-builder-layout">
        <aside id="report-left-panel" class="space-y-3">
            <div class="rb-shell space-y-3 p-3">
                <section class="rb-section">
                    <div class="rb-section-header">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">1) Informasi Rapat</div>
                        </div>
                        <x-filament::icon-button icon="{{ $isStep1Open ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down' }}" color="gray" size="sm" wire:click="toggleBuilderStep(1)" aria-label="Toggle Step 1" />
                    </div>
                    @if ($isStep1Open)
                        <div class="rb-section-body">
                            {{ $this->form }}
                        </div>
                    @endif
                </section>

                <section class="rb-section">
                    <div class="rb-section-header">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">2) Pilih Task</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">{{ count($selectedTaskIds) }} dipilih</span>
                            <x-filament::icon-button icon="{{ $isStep2Open ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down' }}" color="gray" size="sm" wire:click="toggleBuilderStep(2)" aria-label="Toggle Step 2" />
                        </div>
                    </div>
                    @if ($isStep2Open)
                        <div class="rb-section-body space-y-2">
                            <input type="text" wire:model.live.debounce.300ms="taskSearch" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Cari task atau PIC">

                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <select wire:model.live="taskFilterStaffId" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs">
                                    <option value="">All PIC</option>
                                    @foreach ($staffFilterOptions as $staffId => $staffName)
                                        <option value="{{ $staffId }}">{{ $staffName }}</option>
                                    @endforeach
                                </select>
                                <select wire:model.live="taskFilterProjectId" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs">
                                    <option value="">All Project</option>
                                    @foreach ($projectFilterOptions as $projectId => $projectName)
                                        <option value="{{ $projectId }}">{{ $projectName }}</option>
                                    @endforeach
                                </select>
                                <select wire:model.live="taskFilterStatus" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs">
                                    <option value="">All Status</option>
                                    @foreach ($statusFilterOptions as $status)
                                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                    @endforeach
                                </select>
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-2 py-2 text-xs text-gray-700">
                                    <input type="checkbox" wire:model.live="showOnlySelectedTasks" class="rounded border-gray-300">
                                    Show selected only
                                </label>
                            </div>

                            @if (!empty($taskFilterChips))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($taskFilterChips as $chip)
                                        <span class="rb-chip">{{ $chip }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-2">
                                <x-filament::button size="xs" color="primary" icon="heroicon-o-check-circle" wire:click="selectCurrentPageTasks">Pilih Halaman</x-filament::button>
                                <x-filament::button size="xs" color="primary" icon="heroicon-o-funnel" wire:click="selectFilteredTasks">Pilih Filter</x-filament::button>
                                <x-filament::button size="xs" color="danger" icon="heroicon-o-x-circle" wire:click="unselectFilteredTasks">Hapus Filter</x-filament::button>
                            </div>

                            <div class="max-h-72 space-y-1 overflow-auto rounded-lg border border-gray-200 bg-gray-50/60 p-2">
                                @forelse ($availableTasks as $task)
                                    @php $isTaskSelected = in_array((string) $task->id, $selectedTaskIds, true); @endphp
                                    <label class="rb-list-item {{ $isTaskSelected ? 'rb-selected' : '' }} flex cursor-pointer items-start gap-2 px-2 py-2" wire:key="task-picker-{{ $task->id }}">
                                        <input type="checkbox" wire:model.live="selectedTaskIds" value="{{ (string) $task->id }}" class="mt-1 rounded border-gray-300">
                                        <span class="min-w-0 flex-1 text-xs">
                                            <span class="flex items-start justify-between gap-2">
                                                <span class="truncate font-medium text-gray-900">{{ $task->task_name ?: '-' }}</span>
                                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ ((int) ($task->issues_count ?? 0)) > 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-600' }}">
                                                    {{ (int) ($task->issues_count ?? 0) }} issue
                                                </span>
                                            </span>
                                            <span class="mt-0.5 block text-gray-500">PIC: {{ $task->staff?->name ?? '-' }} • Project: {{ $task->project?->project_name ?? '-' }}</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="px-2 py-1 text-xs text-gray-500">Task tidak ditemukan.</p>
                                @endforelse
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-2 py-2 text-xs">
                                <span class="text-gray-600">{{ $taskPickerMeta['from'] ?? 0 }}-{{ $taskPickerMeta['to'] ?? 0 }} / {{ $taskPickerMeta['total'] ?? 0 }}</span>
                                <div class="flex items-center gap-1">
                                    <x-filament::button size="xs" color="gray" wire:click="previousTaskPickerPage" :disabled="($taskPickerMeta['page'] ?? 1) <= 1">Prev</x-filament::button>
                                    <span class="px-1 text-gray-600">{{ $taskPickerMeta['page'] ?? 1 }}/{{ $taskPickerMeta['last_page'] ?? 1 }}</span>
                                    <x-filament::button size="xs" color="gray" wire:click="nextTaskPickerPage" :disabled="($taskPickerMeta['page'] ?? 1) >= ($taskPickerMeta['last_page'] ?? 1)">Next</x-filament::button>
                                </div>
                            </div>

                            <x-filament::button size="xs" color="danger" outlined wire:click="clearSelectedTasks" class="w-full">
                                Clear Semua Pilihan Task
                            </x-filament::button>
                        </div>
                    @endif
                </section>
                <section class="rb-section">
                    <div class="rb-section-header">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">3) Pilih Issue</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">{{ count($selectedIssueIds) }} dipilih</span>
                            <x-filament::icon-button icon="{{ $isStep3Open ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down' }}" color="gray" size="sm" wire:click="toggleBuilderStep(3)" aria-label="Toggle Step 3" />
                        </div>
                    </div>
                    @if ($isStep3Open)
                        <div class="rb-section-body space-y-2">
                            @if (!$canAccessIssueStep)
                                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                                    Pilih minimal satu task di Step 2 untuk mengaktifkan pemilihan issue.
                                </div>
                            @endif

                            <input
                                type="text"
                                wire:model.live.debounce.300ms="issueSearch"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Cari issue, deskripsi, task, PIC"
                                @disabled(!$canAccessIssueStep)
                            >

                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <select wire:model.live="issueFilterStatus" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs" @disabled(!$canAccessIssueStep)>
                                    <option value="">All Issue Status</option>
                                    @foreach ($issueStatusOptions as $status)
                                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                    @endforeach
                                </select>
                                <select wire:model.live="issueFilterPriority" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs" @disabled(!$canAccessIssueStep)>
                                    <option value="">All Priority</option>
                                    @foreach ($issuePriorityOptions as $priority)
                                        <option value="{{ $priority }}">{{ ucfirst(str_replace('_', ' ', $priority)) }}</option>
                                    @endforeach
                                </select>
                                <select wire:model.live="issueFilterStaffId" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs" @disabled(!$canAccessIssueStep)>
                                    <option value="">All PIC Issue</option>
                                    @foreach ($issueStaffOptions as $staffId => $staffName)
                                        <option value="{{ $staffId }}">{{ $staffName }}</option>
                                    @endforeach
                                </select>
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-2 py-2 text-xs text-gray-700">
                                    <input type="checkbox" wire:model.live="showOnlySelectedIssues" class="rounded border-gray-300" @disabled(!$canAccessIssueStep)>
                                    Show selected only
                                </label>
                            </div>

                            @if (!empty($issueFilterChips))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($issueFilterChips as $chip)
                                        <span class="rb-chip">{{ $chip }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-2">
                                <x-filament::button size="xs" color="primary" icon="heroicon-o-check-circle" wire:click="selectCurrentPageIssues" :disabled="!$canAccessIssueStep">Pilih Halaman</x-filament::button>
                                <x-filament::button size="xs" color="primary" icon="heroicon-o-funnel" wire:click="selectFilteredIssues" :disabled="!$canAccessIssueStep">Pilih Filter</x-filament::button>
                                <x-filament::button size="xs" color="danger" icon="heroicon-o-x-circle" wire:click="unselectFilteredIssues" :disabled="!$canAccessIssueStep">Hapus Filter</x-filament::button>
                            </div>

                            <div class="max-h-64 space-y-1 overflow-auto rounded-lg border border-gray-200 bg-gray-50/60 p-2">
                                @if (!$canAccessIssueStep)
                                    <p class="px-2 py-1 text-xs text-gray-500">Pilih task terlebih dahulu untuk menampilkan issue.</p>
                                @else
                                    @forelse ($availableIssues as $issue)
                                        @php $isIssueSelected = in_array((string) $issue->id, $selectedIssueIds, true); @endphp
                                        <label class="rb-list-item {{ $isIssueSelected ? 'rb-selected' : '' }} flex cursor-pointer items-start gap-2 px-2 py-2" wire:key="issue-picker-{{ $issue->id }}">
                                            <input type="checkbox" wire:model.live="selectedIssueIds" value="{{ (string) $issue->id }}" class="mt-1 rounded border-gray-300">
                                            <span class="min-w-0 flex-1 text-xs">
                                                <span class="flex items-start justify-between gap-2">
                                                    <span class="truncate font-medium text-gray-900">{{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($issue->issue_name ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 70, '...') }}</span>
                                                    <span class="flex shrink-0 items-center gap-1">
                                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">{{ (int) ($issue->action_plans_count ?? 0) }} action plan</span>
                                                        <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold text-sky-800">Task: {{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($issue->task?->task_name ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 45, '...') }}</span>
                                                    </span>
                                                </span>
                                                <span class="mt-0.5 block text-gray-500">
                                                    PIC: {{ $issue->staff?->name ?? '-' }} •
                                                    Status: {{ ucfirst(str_replace('_', ' ', (string) ($issue->status ?? '-'))) }} •
                                                    Priority: {{ ucfirst(str_replace('_', ' ', (string) ($issue->priority ?? '-'))) }}
                                                </span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="px-2 py-1 text-xs text-gray-500">Issue tidak ditemukan untuk task yang dipilih.</p>
                                    @endforelse
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-2 py-2 text-xs">
                                <span class="text-gray-600">{{ $issuePickerMeta['from'] ?? 0 }}-{{ $issuePickerMeta['to'] ?? 0 }} / {{ $issuePickerMeta['total'] ?? 0 }}</span>
                                <div class="flex items-center gap-1">
                                    <x-filament::button size="xs" color="gray" wire:click="previousIssuePickerPage" :disabled="($issuePickerMeta['page'] ?? 1) <= 1">Prev</x-filament::button>
                                    <span class="px-1 text-gray-600">{{ $issuePickerMeta['page'] ?? 1 }}/{{ $issuePickerMeta['last_page'] ?? 1 }}</span>
                                    <x-filament::button size="xs" color="gray" wire:click="nextIssuePickerPage" :disabled="($issuePickerMeta['page'] ?? 1) >= ($issuePickerMeta['last_page'] ?? 1)">Next</x-filament::button>
                                </div>
                            </div>

                            <x-filament::button size="xs" color="danger" outlined wire:click="clearSelectedIssues" class="w-full" :disabled="!$canAccessIssueStep">
                                Clear Semua Issue Terpilih
                            </x-filament::button>
                        </div>
                    @endif
                </section>
                <section class="rb-section">
                    <div class="rb-section-header">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">4) Pilih Action Plan</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">{{ count($selectedActionPlanIds) }} dipilih</span>
                            <x-filament::icon-button icon="{{ $isStep4Open ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down' }}" color="gray" size="sm" wire:click="toggleBuilderStep(4)" aria-label="Toggle Step 4" />
                        </div>
                    </div>
                    @if ($isStep4Open)
                        <div class="rb-section-body space-y-3">
                            @if (!$canAccessActionPlanStep)
                                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                                    Pilih minimal satu issue di Step 3 untuk mengaktifkan pemilihan action plan.
                                </div>
                            @else
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="actionPlanSearch"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                    placeholder="Cari action plan, issue, task, PIC"
                                >

                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <select wire:model.live="actionPlanFilterStatus" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs">
                                        <option value="">All Action Plan Status</option>
                                        @foreach ($actionPlanStatusOptions as $status)
                                            <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                    <select wire:model.live="actionPlanFilterStaffId" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs">
                                        <option value="">All PIC Action Plan</option>
                                        @foreach ($actionPlanStaffOptions as $staffId => $staffName)
                                            <option value="{{ $staffId }}">{{ $staffName }}</option>
                                        @endforeach
                                    </select>
                                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-2 py-2 text-xs text-gray-700 sm:col-span-2">
                                        <input type="checkbox" wire:model.live="showOnlySelectedActionPlans" class="rounded border-gray-300">
                                        Show selected only
                                    </label>
                                </div>

                                @if (!empty($actionPlanFilterChips))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($actionPlanFilterChips as $chip)
                                            <span class="rb-chip">{{ $chip }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex flex-wrap items-center gap-2">
                                    <x-filament::button size="xs" color="primary" icon="heroicon-o-check-circle" wire:click="selectCurrentPageActionPlans">Pilih Halaman</x-filament::button>
                                    <x-filament::button size="xs" color="primary" icon="heroicon-o-funnel" wire:click="selectFilteredActionPlans">Pilih Filter</x-filament::button>
                                    <x-filament::button size="xs" color="danger" icon="heroicon-o-x-circle" wire:click="unselectFilteredActionPlans">Hapus Filter</x-filament::button>
                                </div>

                                <div class="max-h-64 space-y-1 overflow-auto rounded-lg border border-gray-200 bg-gray-50/60 p-2">
                                    @forelse ($availableActionPlans as $actionPlan)
                                        @php $isSelectedActionPlan = in_array((string) $actionPlan->id, $selectedActionPlanIds, true); @endphp
                                        <label class="rb-list-item {{ $isSelectedActionPlan ? 'rb-selected' : '' }} flex cursor-pointer items-start gap-2 px-2 py-2" wire:key="action-plan-picker-{{ $actionPlan->id }}">
                                            <input type="checkbox" wire:model.live="selectedActionPlanIds" value="{{ (string) $actionPlan->id }}" class="mt-1 rounded border-gray-300">
                                            <span class="min-w-0 flex-1 text-xs">
                                                <span class="flex items-start justify-between gap-2">
                                                    <span class="truncate font-medium text-gray-900">
                                                        {{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($actionPlan->description ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 80, '...') }}
                                                    </span>
                                                    <span class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold text-sky-800">
                                                        Issue: {{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($actionPlan->issue?->issue_name ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 36, '...') }}
                                                    </span>
                                                </span>
                                                <span class="mt-0.5 block text-gray-500">
                                                    Task: {{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($actionPlan->issue?->task?->task_name ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 36, '...') }} •
                                                    PIC: {{ $actionPlan->pic?->name ?? '-' }} •
                                                    Status: {{ ucfirst(str_replace('_', ' ', (string) ($actionPlan->status ?? '-'))) }}
                                                </span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="px-2 py-1 text-xs text-gray-500">Action plan tidak ditemukan untuk issue yang dipilih.</p>
                                    @endforelse
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-2 py-2 text-xs">
                                    <span class="text-gray-600">{{ $actionPlanPickerMeta['from'] ?? 0 }}-{{ $actionPlanPickerMeta['to'] ?? 0 }} / {{ $actionPlanPickerMeta['total'] ?? 0 }}</span>
                                    <div class="flex items-center gap-1">
                                        <x-filament::button size="xs" color="gray" wire:click="previousActionPlanPickerPage" :disabled="($actionPlanPickerMeta['page'] ?? 1) <= 1">Prev</x-filament::button>
                                        <span class="px-1 text-gray-600">{{ $actionPlanPickerMeta['page'] ?? 1 }}/{{ $actionPlanPickerMeta['last_page'] ?? 1 }}</span>
                                        <x-filament::button size="xs" color="gray" wire:click="nextActionPlanPickerPage" :disabled="($actionPlanPickerMeta['page'] ?? 1) >= ($actionPlanPickerMeta['last_page'] ?? 1)">Next</x-filament::button>
                                    </div>
                                </div>

                                <x-filament::button size="xs" color="danger" outlined wire:click="clearSelectedActionPlans" class="w-full">
                                    Clear Semua Action Plan Terpilih
                                </x-filament::button>
                            @endif
                        </div>
                    @endif
                </section>
                <section class="rb-section">
                    <div class="rb-section-header">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">5) Urutan & Final Check</div>
                        </div>
                        <x-filament::icon-button icon="{{ $isStep5Open ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down' }}" color="gray" size="sm" wire:click="toggleBuilderStep(5)" aria-label="Toggle Step 5" />
                    </div>
                    @if ($isStep5Open)
                        <div class="rb-section-body space-y-3">
                            @if (!$canAccessFinalStep)
                                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                                    Step ini aktif setelah task dipilih.
                                </div>
                            @else
                                <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">Urutan Task di Report</div>
                                        <div class="flex flex-wrap gap-1">
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-path" wire:click="resetTaskOrder">Reset</x-filament::button>
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-list-bullet" wire:click="sortSelectedTasksByName">Sort Nama</x-filament::button>
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-calendar-days" wire:click="sortSelectedTasksByTargetDate">Sort Target</x-filament::button>
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-uturn-left" wire:click="undoTaskOrder">Undo</x-filament::button>
                                        </div>
                                    </div>
                                    <div class="space-y-1" data-dnd-list="task">
                                        @if (($selectedTasksOrdered ?? collect())->isNotEmpty())
                                            @foreach ($selectedTasksOrdered as $taskIndex => $selectedTask)
                                                <div
                                                    class="dnd-sort-item flex items-center justify-between gap-2 px-2 py-1.5"
                                                    wire:key="task-order-{{ $selectedTask->id }}"
                                                    data-dnd-item="{{ $selectedTask->id }}"
                                                    draggable="true"
                                                >
                                                    <div class="flex min-w-0 items-start gap-2 text-xs">
                                                        <span class="dnd-sort-handle mt-0.5" aria-hidden="true">⋮⋮</span>
                                                        <div class="min-w-0">
                                                            <div class="truncate font-medium text-gray-900">{{ $taskIndex + 1 }}. {{ $selectedTask->task_name ?: '-' }}</div>
                                                            <div class="truncate text-[11px] text-gray-500">{{ $selectedTask->staff?->name ?? '-' }} • {{ $selectedTask->project?->project_name ?? '-' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex shrink-0 items-center gap-1" data-dnd-ignore="true">
                                                        <x-filament::icon-button size="xs" color="gray" icon="heroicon-m-chevron-up" wire:click="moveTaskSelectionUp('{{ $selectedTask->id }}')" aria-label="Naikkan task" />
                                                        <x-filament::icon-button size="xs" color="gray" icon="heroicon-m-chevron-down" wire:click="moveTaskSelectionDown('{{ $selectedTask->id }}')" aria-label="Turunkan task" />
                                                        <x-filament::icon-button size="xs" color="danger" icon="heroicon-m-x-mark" wire:click="removeSelectedTask('{{ $selectedTask->id }}')" aria-label="Hapus task terpilih" />
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="px-1 py-1 text-xs text-gray-500">Belum ada task terpilih.</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">Urutan Issue di Report</div>
                                        <div class="flex flex-wrap gap-1">
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-path" wire:click="resetIssueOrder">Reset</x-filament::button>
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-list-bullet" wire:click="sortSelectedIssuesByName">Sort Nama</x-filament::button>
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-uturn-left" wire:click="undoIssueOrder">Undo</x-filament::button>
                                        </div>
                                    </div>
                                    <div class="space-y-1" data-dnd-list="issue">
                                        @if (($selectedIssuesOrdered ?? collect())->isNotEmpty())
                                            @foreach ($selectedIssuesOrdered as $issueIndex => $selectedIssue)
                                                <div
                                                    class="dnd-sort-item flex items-center justify-between gap-2 px-2 py-1.5"
                                                    wire:key="issue-order-{{ $selectedIssue->id }}"
                                                    data-dnd-item="{{ $selectedIssue->id }}"
                                                    draggable="true"
                                                >
                                                    <div class="flex min-w-0 items-start gap-2 text-xs">
                                                        <span class="dnd-sort-handle mt-0.5" aria-hidden="true">⋮⋮</span>
                                                        <div class="min-w-0">
                                                            <div class="truncate font-medium text-gray-900">{{ $issueIndex + 1 }}. {{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($selectedIssue->issue_name ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 80, '...') }}</div>
                                                            <div class="truncate text-[11px] text-gray-500">Task: {{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($selectedIssue->task?->task_name ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 45, '...') }} • PIC: {{ $selectedIssue->staff?->name ?? '-' }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex shrink-0 items-center gap-1" data-dnd-ignore="true">
                                                        <x-filament::icon-button size="xs" color="gray" icon="heroicon-m-chevron-up" wire:click="moveIssueSelectionUp('{{ $selectedIssue->id }}')" aria-label="Naikkan issue" />
                                                        <x-filament::icon-button size="xs" color="gray" icon="heroicon-m-chevron-down" wire:click="moveIssueSelectionDown('{{ $selectedIssue->id }}')" aria-label="Turunkan issue" />
                                                        <x-filament::icon-button size="xs" color="danger" icon="heroicon-m-x-mark" wire:click="removeSelectedIssue('{{ $selectedIssue->id }}')" aria-label="Hapus issue terpilih" />
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="px-1 py-1 text-xs text-gray-500">Belum ada issue terpilih.</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">Urutan Action Plan di Report</div>
                                        <div class="flex flex-wrap gap-1">
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-path" wire:click="resetActionPlanOrder">Reset</x-filament::button>
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-list-bullet" wire:click="sortSelectedActionPlansByDescription">Sort Deskripsi</x-filament::button>
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-link" wire:click="sortSelectedActionPlansByIssue">Sort Issue</x-filament::button>
                                            <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-uturn-left" wire:click="undoActionPlanOrder">Undo</x-filament::button>
                                        </div>
                                    </div>
                                    <div class="space-y-1" data-dnd-list="action-plan">
                                        @if (($selectedActionPlansOrdered ?? collect())->isNotEmpty())
                                            @foreach ($selectedActionPlansOrdered as $actionPlanIndex => $selectedActionPlan)
                                                <div
                                                    class="dnd-sort-item flex items-center justify-between gap-2 px-2 py-1.5"
                                                    wire:key="action-plan-order-{{ $selectedActionPlan->id }}"
                                                    data-dnd-item="{{ $selectedActionPlan->id }}"
                                                    draggable="true"
                                                >
                                                    <div class="flex min-w-0 items-start gap-2 text-xs">
                                                        <span class="dnd-sort-handle mt-0.5" aria-hidden="true">⋮⋮</span>
                                                        <div class="min-w-0">
                                                            <div class="truncate font-medium text-gray-900">{{ $actionPlanIndex + 1 }}. {{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($selectedActionPlan->description ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 80, '...') }}</div>
                                                            <div class="truncate text-[11px] text-gray-500">
                                                                Issue: {{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($selectedActionPlan->issue?->issue_name ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 34, '...') }} •
                                                                Task: {{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) ($selectedActionPlan->issue?->task?->task_name ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 28, '...') }} •
                                                                PIC: {{ $selectedActionPlan->pic?->name ?? '-' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex shrink-0 items-center gap-1" data-dnd-ignore="true">
                                                        <x-filament::icon-button size="xs" color="gray" icon="heroicon-m-chevron-up" wire:click="moveActionPlanSelectionUp('{{ $selectedActionPlan->id }}')" aria-label="Naikkan action plan" />
                                                        <x-filament::icon-button size="xs" color="gray" icon="heroicon-m-chevron-down" wire:click="moveActionPlanSelectionDown('{{ $selectedActionPlan->id }}')" aria-label="Turunkan action plan" />
                                                        <x-filament::icon-button size="xs" color="danger" icon="heroicon-m-x-mark" wire:click="removeSelectedActionPlan('{{ $selectedActionPlan->id }}')" aria-label="Hapus action plan terpilih" />
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="px-1 py-1 text-xs text-gray-500">Belum ada action plan terpilih.</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </section>
            </div>
        </aside>

        <section id="report-right-preview" class="min-w-0 {{ $zoomClass }}">
            <div class="rb-preview-toolbar mb-2 space-y-2">
                <div class="rb-preview-meta">
                    <div class="rb-preview-meta-left">
                        @if ($previewDirty)
                            <span class="rb-chip rb-chip-warning">Changes not rendered</span>
                        @else
                            <span class="rb-chip rb-chip-success">Preview updated</span>
                        @endif
                        @if (!empty($editingHistoryId))
                            <span class="rb-chip">Edit History #{{ $editingHistoryId }} - sumber V{{ $editingVersionNo }}</span>
                        @endif
                    </div>
                    <div class="rb-preview-meta-right">
                        <span class="text-gray-500">Last sync: {{ $lastPreviewAt ?: '-' }}</span>
                        <span class="text-gray-500">Last rendered: {{ $lastRenderedAt ?: '-' }}</span>
                    </div>
                </div>

                <div class="rb-preview-actions">
                    <div class="rb-preview-actions-left">
                        @if (!empty($editingHistoryId) && !empty($historyVersionOptions))
                            <label class="rb-preview-version-label" for="history-version-select">Versi</label>
                            <select
                                id="history-version-select"
                                wire:model.live="selectedHistoryVersionId"
                                class="rb-preview-version-select"
                            >
                                @foreach ($historyVersionOptions as $versionHistoryId => $versionLabel)
                                    <option value="{{ $versionHistoryId }}">{{ $versionLabel }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="rb-preview-actions-right">
                        <x-filament::button size="sm" color="gray" wire:click="refreshPreviewState">Refresh Preview</x-filament::button>
                        <x-filament::button size="sm" color="gray" icon="heroicon-o-adjustments-horizontal" wire:click="mountAction('manageColumnWidths')">Lebar Kolom</x-filament::button>
                        <x-filament::button size="sm" color="gray" icon="heroicon-o-document-text" disabled x-tooltip="{ content: 'Sementara dinonaktifkan' }">Render DOCX</x-filament::button>
                        <x-filament::button size="sm" icon="heroicon-o-document-arrow-down" wire:click="mountAction('renderPdf')">Render PDF</x-filament::button>
                    </div>
                </div>
            </div>

            <div id="report-right-preview-scroll">
                <div class="space-y-4">
                    @if (($previewTotalPages ?? 1) > 1)
                        @php
                            $pages = $previewPages ?? [];
                            if (empty($pages)) {
                                $pages = [[
                                    'rows' => $previewRows ?? [],
                                    'showCover' => true,
                                    'showSignatures' => true,
                                    'signaturePushPx' => (int) ($signaturePushPx ?? 0),
                                    'pageNumber' => 1,
                                    'totalPages' => 1,
                                ]];
                            }
                        @endphp

                        @foreach ($pages as $page)
                            <div class="a4-sheet mx-auto overflow-hidden">
                                <div class="report-doc">
                                    @include('filament.pages.partials.task-report-preview-page', [
                                        'pageRows' => $page['rows'] ?? [],
                                        'showCover' => (bool) ($page['showCover'] ?? false),
                                        'showSignatures' => (bool) ($page['showSignatures'] ?? false),
                                        'signaturePushPx' => (int) ($page['signaturePushPx'] ?? 0),
                                        'pageNumber' => (int) ($page['pageNumber'] ?? 1),
                                        'totalPages' => (int) ($page['totalPages'] ?? 1),
                                        'isInteractivePreview' => true,
                                    ])
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="a4-sheet mx-auto overflow-hidden">
                            <div class="report-doc">
                                @include('filament.pages.partials.task-report-document', [
                                    'isInteractivePreview' => true,
                                ])
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <script>
        (() => {
            if (window.__taskReportDnDInitialized) {
                return;
            }
            window.__taskReportDnDInitialized = true;

            let draggingElement = null;
            let draggingType = null;
            let currentTarget = null;

            const clearDropTarget = () => {
                if (currentTarget) {
                    currentTarget.classList.remove('dnd-target-before');
                    currentTarget = null;
                }
            };

            const getAfterElement = (container, mouseY) => {
                const items = [...container.querySelectorAll('[data-dnd-item]:not(.dnd-dragging)')];
                let closest = { offset: Number.NEGATIVE_INFINITY, element: null };

                items.forEach((item) => {
                    const box = item.getBoundingClientRect();
                    const offset = mouseY - box.top - (box.height / 2);
                    if (offset < 0 && offset > closest.offset) {
                        closest = { offset, element: item };
                    }
                });

                return closest.element;
            };

            const autoScroll = (container, clientY) => {
                const rect = container.getBoundingClientRect();
                const threshold = 32;
                if (clientY < rect.top + threshold) {
                    container.scrollTop -= 14;
                } else if (clientY > rect.bottom - threshold) {
                    container.scrollTop += 14;
                }
            };

            const syncOrder = (container) => {
                const ids = [...container.querySelectorAll('[data-dnd-item]')]
                    .map((element) => element.getAttribute('data-dnd-item'))
                    .filter(Boolean);

                const host = container.closest('[wire\\:id]');
                if (!host || !window.Livewire) {
                    return;
                }

                const component = window.Livewire.find(host.getAttribute('wire:id'));
                if (!component) {
                    return;
                }

                const listType = container.getAttribute('data-dnd-list');
                if (listType === 'task') {
                    component.call('reorderSelectedTasks', ids);
                } else if (listType === 'issue') {
                    component.call('reorderSelectedIssues', ids);
                } else if (listType === 'action-plan') {
                    component.call('reorderSelectedActionPlans', ids);
                }
            };

            document.addEventListener('dragstart', (event) => {
                if (event.target.closest('[data-dnd-ignore]')) {
                    return;
                }

                const item = event.target.closest('[data-dnd-item]');
                if (!item) {
                    return;
                }

                const container = item.closest('[data-dnd-list]');
                if (!container) {
                    return;
                }

                draggingElement = item;
                draggingType = container.getAttribute('data-dnd-list');
                item.classList.add('dnd-dragging');

                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', item.getAttribute('data-dnd-item') || '');
                }
            });

            document.addEventListener('dragover', (event) => {
                if (!draggingElement || !draggingType) {
                    return;
                }

                const container = event.target.closest('[data-dnd-list]');
                if (!container || container.getAttribute('data-dnd-list') !== draggingType) {
                    return;
                }

                event.preventDefault();
                autoScroll(container, event.clientY);

                const afterElement = getAfterElement(container, event.clientY);
                clearDropTarget();

                if (afterElement === null) {
                    container.appendChild(draggingElement);
                } else if (afterElement !== draggingElement) {
                    container.insertBefore(draggingElement, afterElement);
                    currentTarget = afterElement;
                    currentTarget.classList.add('dnd-target-before');
                }
            });

            document.addEventListener('drop', (event) => {
                if (!draggingElement || !draggingType) {
                    return;
                }

                const container = event.target.closest('[data-dnd-list]');
                if (!container || container.getAttribute('data-dnd-list') !== draggingType) {
                    return;
                }

                event.preventDefault();
                clearDropTarget();
                syncOrder(container);
            });

            document.addEventListener('dragend', () => {
                if (!draggingElement) {
                    return;
                }

                const container = draggingElement.closest('[data-dnd-list]');
                draggingElement.classList.remove('dnd-dragging');
                clearDropTarget();

                if (container) {
                    syncOrder(container);
                }

                draggingElement = null;
                draggingType = null;
            });

            // Column resize (Excel-like splitter on preview header)
            let resizeContext = null;
            const minColumnPercent = 3;

            const parseCurrentColumnWidths = (table) => {
                const cols = [...table.querySelectorAll('colgroup col[data-col-key]')];
                const widths = {};

                cols.forEach((col) => {
                    const key = col.getAttribute('data-col-key');
                    const value = parseFloat(col.style.width || '0');
                    if (key) {
                        widths[key] = Number.isFinite(value) ? value : 0;
                    }
                });

                return widths;
            };

            const applyWidthsToPreviewTables = (widths) => {
                document
                    .querySelectorAll('#report-right-preview table[data-resizable-report-table="1"]')
                    .forEach((table) => {
                        table.querySelectorAll('colgroup col[data-col-key]').forEach((col) => {
                            const key = col.getAttribute('data-col-key');
                            if (!key || typeof widths[key] === 'undefined') {
                                return;
                            }

                            col.style.width = `${Number(widths[key]).toFixed(2)}%`;
                        });
                    });
            };

            const persistResizedWidths = (wireId, widths) => {
                if (!wireId || !window.Livewire) {
                    return;
                }

                const component = window.Livewire.find(wireId);
                if (!component) {
                    return;
                }

                component.call('applyColumnResize', widths);
            };

            document.addEventListener('mousedown', (event) => {
                const handle = event.target.closest('.preview-col-resize-handle');
                if (!handle) {
                    return;
                }

                const table = handle.closest('table[data-resizable-report-table="1"]');
                if (!table) {
                    return;
                }

                const leftKey = handle.getAttribute('data-resize-left');
                const rightKey = handle.getAttribute('data-resize-right');
                if (!leftKey || !rightKey) {
                    return;
                }

                const initialWidths = parseCurrentColumnWidths(table);
                const leftStart = Number(initialWidths[leftKey] ?? 0);
                const rightStart = Number(initialWidths[rightKey] ?? 0);
                const pairTotal = leftStart + rightStart;

                if (pairTotal <= 0) {
                    return;
                }

                event.preventDefault();
                handle.classList.add('preview-col-resize-active');

                resizeContext = {
                    handle,
                    tableWidth: Math.max(1, table.getBoundingClientRect().width),
                    startX: event.clientX,
                    leftKey,
                    rightKey,
                    leftStart,
                    pairTotal,
                    widths: initialWidths,
                    wireId: table.closest('[wire\\:id]')?.getAttribute('wire:id') ?? null,
                };
            });

            document.addEventListener('mousemove', (event) => {
                if (!resizeContext) {
                    return;
                }

                const deltaPercent = ((event.clientX - resizeContext.startX) / resizeContext.tableWidth) * 100;
                const minLeft = minColumnPercent;
                const maxLeft = resizeContext.pairTotal - minColumnPercent;
                const nextLeft = Math.min(maxLeft, Math.max(minLeft, resizeContext.leftStart + deltaPercent));
                const nextRight = resizeContext.pairTotal - nextLeft;

                const nextWidths = {
                    ...resizeContext.widths,
                    [resizeContext.leftKey]: Number(nextLeft.toFixed(2)),
                    [resizeContext.rightKey]: Number(nextRight.toFixed(2)),
                };

                resizeContext.widths = nextWidths;
                applyWidthsToPreviewTables(nextWidths);
            });

            document.addEventListener('mouseup', () => {
                if (!resizeContext) {
                    return;
                }

                resizeContext.handle.classList.remove('preview-col-resize-active');
                persistResizedWidths(resizeContext.wireId, resizeContext.widths);
                resizeContext = null;
            });
        })();
    </script>
</x-filament-panels::page>

