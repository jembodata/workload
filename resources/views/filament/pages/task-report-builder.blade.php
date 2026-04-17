<x-filament-panels::page>
    <x-filament-actions::modals />

    @php
        $collapsed = $collapsedBuilderSteps ?? [];
        $isStep1Open = !($collapsed['1'] ?? false);
        $isStep2Open = !($collapsed['2'] ?? false);
        $isStep3Open = !($collapsed['3'] ?? false);
        $isStep4Open = !($collapsed['4'] ?? false);
        $zoomClass = 'preview-zoom-' . str_replace('.', '-', $previewZoom ?? 'fit');
    @endphp

    <style>
        .fi-page div.grid.flex-1.auto-cols-fr.gap-y-8 {
            display: block !important;
            gap: 0 !important;
            row-gap: 0 !important;
        }

        :root {
            --a4-preview-width: 794px;
            --a4-preview-height: 1123px;
        }

        #report-builder-layout {
            display: none;
            position: relative;
            margin-top: 0;
            padding-top: 0;
        }

        #report-left-panel {
            width: 100%;
            margin-top: -20px;
        }

        #report-right-preview {
            width: 100%;
        }

        #report-right-preview .a4-sheet {
            width: min(var(--a4-preview-width), 100%);
            min-height: var(--a4-preview-height);
            max-width: 100%;
            padding: 12mm;
            border: 1px solid #dfe5ee;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.10);
            overflow: hidden;
        }

        #report-right-preview.preview-zoom-0-75 .a4-sheet { width: min(596px, 100%); }
        #report-right-preview.preview-zoom-1 .a4-sheet { width: min(var(--a4-preview-width), 100%); }
        #report-right-preview.preview-zoom-fit .a4-sheet { width: 100%; max-width: var(--a4-preview-width); }

        #report-right-preview .report-doc { font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; color: #000; }
        #report-right-preview .report-doc .center { text-align: center; }
        #report-right-preview .report-doc table { border-collapse: collapse; border-spacing: 0; width: 100%; }
        #report-right-preview .report-doc .document-container { width: 100%; }
        #report-right-preview .report-doc .header-table { border: 0.75px solid #000; table-layout: fixed; }
        #report-right-preview .report-doc .details-table,
        #report-right-preview .report-doc .data-table-section { border-left: 0.75px solid #000; border-right: 0.75px solid #000; border-bottom: 0.75px solid #000; border-top: 0; }
        #report-right-preview .report-doc .header-table td { border: 0.75px solid #000; padding: 0; }
        #report-right-preview .report-doc .logo-cell { width: 18%; text-align: center; vertical-align: middle; padding: 7px 5px; }
        #report-right-preview .report-doc .logo-wrap { width: 100%; height: 56px; margin: 0 auto; text-align: center; overflow: hidden; white-space: nowrap; }
        #report-right-preview .report-doc .logo-wrap img { width: auto; height: auto; max-width: 112px; max-height: 56px; display: inline-block; vertical-align: middle; }
        #report-right-preview .report-doc .title-cell { text-align: center; vertical-align: middle; }
        #report-right-preview .report-doc .main-title { margin: 0; font-size: 14px; font-weight: 700; line-height: 1.1; }
        #report-right-preview .report-doc .sub-title { margin-top: 2px; font-size: 10.5px; font-style: italic; font-weight: 700; color: #0054a6; }
        #report-right-preview .report-doc .info-cell { width: 25%; vertical-align: top; padding: 0; }
        #report-right-preview .report-doc .info-table td { border: 0.75px solid #000; font-size: 10px; padding: 2px 5px; line-height: 1.2; vertical-align: middle; }
        #report-right-preview .report-doc .info-table tr:first-child td { border-top: 0; }
        #report-right-preview .report-doc .info-table tr:last-child td { border-bottom: 0; }
        #report-right-preview .report-doc .info-table td:first-child { border-left: 0; }
        #report-right-preview .report-doc .info-table td:last-child { border-right: 0; }
        #report-right-preview .report-doc .info-table .label { width: 45%; white-space: nowrap; font-weight: 700; }
        #report-right-preview .report-doc .details-table { table-layout: fixed; }
        #report-right-preview .report-doc .details-table td { border: 0.75px solid #000; padding: 2px 5px; }
        #report-right-preview .report-doc .details-table tr:first-child td { border-top: 0; }
        #report-right-preview .report-doc .detail-label { width: 18%; white-space: nowrap; font-weight: 700; }
        #report-right-preview .report-doc .detail-value { width: 82%; }
        #report-right-preview .report-doc .data-table-section { table-layout: fixed; }
        #report-right-preview .report-doc .data-table-section th,
        #report-right-preview .report-doc .data-table-section td { border: 0.75px solid #000; padding: 4px; vertical-align: top; }
        #report-right-preview .report-doc .data-table-section thead tr:first-child th { border-top: 0; }
        #report-right-preview .report-doc .data-table-section th { text-align: center; font-size: 10px; font-weight: 700; }
        #report-right-preview .report-doc .col-no { width: 4%; }
        #report-right-preview .report-doc .col-item { width: 14%; }
        #report-right-preview .report-doc .col-pembahasan { width: 16%; }
        #report-right-preview .report-doc .col-rencana { width: 36%; }
        #report-right-preview .report-doc .col-target { width: 10%; }
        #report-right-preview .report-doc .col-pic { width: 10%; }
        #report-right-preview .report-doc .col-evaluasi { width: 10%; }
        #report-right-preview .report-doc .eval-stack { display: block; }
        #report-right-preview .report-doc .eval-pill { display: block; border: 0.75px solid #000; border-radius: 2px; text-align: center; font-size: 9px; font-weight: 700; line-height: 1.25; padding: 2px 3px; }
        #report-right-preview .report-doc .eval-pill + .eval-pill { margin-top: 3px; }
        #report-right-preview .report-doc .eval-tbd { background: #e5e7eb; color: #111827; }
        #report-right-preview .report-doc .eval-progress { background: #fde047; color: #111827; }
        #report-right-preview .report-doc .eval-open { background: #93c5fd; color: #111827; }
        #report-right-preview .report-doc .eval-overdue { background: #fca5a5; color: #111827; }
        #report-right-preview .report-doc .eval-postponed { background: #d1d5db; color: #111827; }
        #report-right-preview .report-doc .eval-closed { background: #86efac; color: #111827; }
        #report-right-preview .report-doc .empty-message { color: #6b7280; padding: 9px 5px; }
        #report-right-preview .report-doc .signature-section { width: 52%; padding: 0; margin-left: auto; margin-right: 0; }
        #report-right-preview .report-doc .signature-table { width: 100%; border-collapse: collapse; table-layout: fixed; border: 0 !important; }
        #report-right-preview .report-doc .signature-cell { width: 33.333%; text-align: center; vertical-align: top; padding: 0 8px; border: 0 !important; }
        #report-right-preview .report-doc .signature-cell-empty { padding: 0; }
        #report-right-preview .report-doc .signature-label { font-size: 10px; }
        #report-right-preview .report-doc .signature-space { height: 34px; }
        #report-right-preview .report-doc .signature-line { border-top: 0.75px solid #000; margin: 0 8px 2px; }
        #report-right-preview .report-doc .signature-name { font-size: 10px; font-weight: 700; }
        #report-right-preview .report-doc .signature-role { font-size: 9.5px; color: #222; }

        .rb-shell { border: 1px solid #d9e0ea; border-radius: 14px; background: #fff; box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05); overflow: hidden; }
        .rb-section { border: 1px solid #d9e0ea; border-radius: 12px; background: #fff; overflow: hidden; }
        .rb-section-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-bottom: 1px solid #eef2f7; background: #f8fafc; }
        .rb-section-body { padding: 12px 14px; }
        .rb-chip { display: inline-flex; align-items: center; border: 1px solid #dbe4f0; border-radius: 999px; background: #f8fafc; color: #334155; font-size: 11px; line-height: 1; padding: 4px 8px; }
        .rb-list-item { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; transition: 0.2s ease; }
        .rb-list-item:hover { border-color: #bfdbfe; background: #f8fbff; }
        .rb-list-item.rb-selected { border-color: #93c5fd; background: #eff6ff; }

        .dnd-sort-item { cursor: grab; user-select: none; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; }
        .dnd-sort-handle { cursor: grab; color: #64748b; font-size: 16px; line-height: 1; }
        .dnd-sort-item:active .dnd-sort-handle { cursor: grabbing; }
        .dnd-sort-item.dnd-dragging { opacity: .55; border-style: dashed; }
        .dnd-target-before { border-top: 2px solid #2563eb !important; }

        .rb-preview-toolbar { position: sticky; top: 0; z-index: 4; border: 1px solid #d9e0ea; border-radius: 12px; background: #fff; padding: 10px; }
        #report-right-preview-scroll { padding: 14px; border: 1px solid #d9dee7; border-radius: 14px; background: #edf1f6; box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06); max-height: calc(100vh - 11rem); overflow: auto; }

        #report-non-desktop-notice { display: block; margin: 0 0 12px 0; padding: 10px 12px; border-radius: 10px; border: 1px solid #fde68a; background: #fffbeb; color: #92400e; font-size: 12px; line-height: 1.4; }

        @media (min-width: 1280px) {
            #report-non-desktop-notice { display: none; }
            #report-builder-layout { display: block; padding-right: calc(min(48vw, 900px) + 24px); margin-top: 0; }
            #report-left-panel { width: auto; min-width: 640px; max-width: 760px; margin-top: -24px; }
            #report-right-preview { position: fixed; right: 1rem; top: 5rem; z-index: 1; width: min(48vw, 900px); }
        }
    </style>

    <div id="report-non-desktop-notice">
        Preview report paling akurat untuk layar desktop (>= 1280px). Di layar kecil, gunakan tombol <strong>Render PDF</strong> untuk hasil final ukuran A4.
    </div>

    <div id="report-builder-layout">
        <aside id="report-left-panel" class="space-y-3" style="max-width: 760px;">
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
                            <div class="text-[11px] text-gray-500">Pilih Halaman untuk batch kecil, Pilih Hasil Filter untuk semua hasil saat ini.</div>

                            <div class="max-h-72 space-y-1 overflow-auto rounded-lg border border-gray-200 bg-gray-50/60 p-2">
                                @forelse ($availableTasks as $task)
                                    @php $isTaskSelected = in_array((string) $task->id, $selectedTaskIds, true); @endphp
                                    <label class="rb-list-item {{ $isTaskSelected ? 'rb-selected' : '' }} flex cursor-pointer items-start gap-2 px-2 py-2" wire:key="task-picker-{{ $task->id }}">
                                        <input type="checkbox" wire:model.live="selectedTaskIds" value="{{ (string) $task->id }}" class="mt-1 rounded border-gray-300">
                                        <span class="min-w-0 flex-1 text-xs">
                                            <span class="flex items-start justify-between gap-2">
                                                <span class="truncate font-medium text-gray-900">{{ $task->task_name ?: '-' }}</span>
                                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ ((int) ($task->issues_count ?? 0)) > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }}">
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
                            <div class="text-[11px] text-gray-500">Filter issue mengikuti task terpilih agar hasil report tetap relevan.</div>

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
                                                    <span class="truncate font-medium text-gray-900">{{ $issue->issue_name ?: '-' }}</span>
                                                    <span class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold text-sky-800">Task: {{ $issue->task?->task_name ?? '-' }}</span>
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
                            <div class="text-sm font-semibold text-gray-900">4) Urutan & Final Check</div>
                        </div>
                        <x-filament::icon-button icon="{{ $isStep4Open ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down' }}" color="gray" size="sm" wire:click="toggleBuilderStep(4)" aria-label="Toggle Step 4" />
                    </div>
                    @if ($isStep4Open)
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
                                                            <div class="truncate font-medium text-gray-900">{{ $issueIndex + 1 }}. {{ $selectedIssue->issue_name ?: '-' }}</div>
                                                            <div class="truncate text-[11px] text-gray-500">Task: {{ $selectedIssue->task?->task_name ?? '-' }} • PIC: {{ $selectedIssue->staff?->name ?? '-' }}</div>
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
                            @endif
                        </div>
                    @endif
                </section>
            </div>
        </aside>

        <section id="report-right-preview" class="min-w-0 {{ $zoomClass }}" style="margin-top: 0;">
            <div class="rb-preview-toolbar mb-2 space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        @if ($previewDirty)
                            <span class="rb-chip" style="border-color:#fbbf24;background:#fffbeb;color:#92400e;">Changes not rendered</span>
                        @else
                            <span class="rb-chip" style="border-color:#86efac;background:#f0fdf4;color:#166534;">Preview updated</span>
                        @endif
                        <span class="text-gray-500">Last sync: {{ $lastPreviewAt ?: '-' }}</span>
                        <span class="text-gray-500">Last rendered: {{ $lastRenderedAt ?: '-' }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-filament::button size="sm" color="gray" wire:click="refreshPreviewState">Refresh Preview</x-filament::button>
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
                                    ])
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="a4-sheet mx-auto overflow-hidden">
                            <div class="report-doc">
                                @include('filament.pages.partials.task-report-document')
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
        })();
    </script>
</x-filament-panels::page>
