<x-filament-panels::page>
    <x-filament-actions::modals />

    <style>
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
            margin-top: -28px;
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
        }

        #report-right-preview .report-doc {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #000;
        }
        #report-right-preview .report-doc .center { text-align: center; }
        #report-right-preview .report-doc table { border-collapse: collapse; border-spacing: 0; width: 100%; }
        #report-right-preview .report-doc .document-container { width: 100%; }
        #report-right-preview .report-doc .header-table { border: 0.75px solid #000; table-layout: fixed; }
        #report-right-preview .report-doc .details-table,
        #report-right-preview .report-doc .data-table-section {
            border-left: 0.75px solid #000;
            border-right: 0.75px solid #000;
            border-bottom: 0.75px solid #000;
            border-top: 0;
        }

        #report-right-preview .report-doc .header-table td { border: 0.75px solid #000; padding: 0; }
        #report-right-preview .report-doc .logo-cell {
            width: 18%;
            text-align: center;
            vertical-align: middle;
            padding: 7px 5px;
        }
        #report-right-preview .report-doc .logo-wrap {
            width: 100%;
            height: 56px;
            margin: 0 auto;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
        }
        #report-right-preview .report-doc .logo-wrap img {
            width: auto;
            height: auto;
            max-width: 112px;
            max-height: 56px;
            display: inline-block;
            vertical-align: middle;
        }
        #report-right-preview .report-doc .title-cell { text-align: center; vertical-align: middle; }
        #report-right-preview .report-doc .main-title { margin: 0; font-size: 14px; font-weight: 700; line-height: 1.1; }
        #report-right-preview .report-doc .sub-title { margin-top: 2px; font-size: 10.5px; font-style: italic; font-weight: 700; color: #0054a6; }
        #report-right-preview .report-doc .info-cell { width: 25%; vertical-align: top; padding: 0; }
        #report-right-preview .report-doc .info-table td {
            border: 0.75px solid #000;
            font-size: 10px;
            padding: 2px 5px;
            line-height: 1.2;
            vertical-align: middle;
        }
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
        #report-right-preview .report-doc .data-table-section td {
            border: 0.75px solid #000;
            padding: 4px;
            vertical-align: top;
        }
        #report-right-preview .report-doc .data-table-section thead tr:first-child th { border-top: 0; }
        #report-right-preview .report-doc .data-table-section th { text-align: center; font-size: 10px; font-weight: 700; }
        #report-right-preview .report-doc .col-no { width: 4%; }
        #report-right-preview .report-doc .col-item { width: 14%; }
        #report-right-preview .report-doc .col-pembahasan { width: 16%; }
        #report-right-preview .report-doc .col-rencana { width: 36%; }
        #report-right-preview .report-doc .col-target { width: 10%; }
        #report-right-preview .report-doc .col-pic { width: 10%; }
        #report-right-preview .report-doc .col-evaluasi { width: 10%; }
        #report-right-preview .report-doc .eval-stack {
            display: block;
        }
        #report-right-preview .report-doc .eval-pill {
            display: block;
            border: 0.75px solid #000;
            border-radius: 2px;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.25;
            padding: 2px 3px;
        }
        #report-right-preview .report-doc .eval-pill + .eval-pill { margin-top: 3px; }
        #report-right-preview .report-doc .eval-spacer { height: 16px; }
        #report-right-preview .report-doc .eval-tbd { background: #e5e7eb; color: #111827; }
        #report-right-preview .report-doc .eval-progress { background: #fde047; color: #111827; }
        #report-right-preview .report-doc .eval-open { background: #93c5fd; color: #111827; }
        #report-right-preview .report-doc .eval-overdue { background: #fca5a5; color: #111827; }
        #report-right-preview .report-doc .eval-postponed { background: #d1d5db; color: #111827; }
        #report-right-preview .report-doc .eval-closed { background: #86efac; color: #111827; }
        #report-right-preview .report-doc .empty-message { color: #6b7280; padding: 9px 5px; }
        #report-right-preview .report-doc .signature-section { width: 52%; padding: 0; margin-left: auto; margin-right: 0; }
        #report-right-preview .report-doc .signature-section.signature-section--flow {
            display: block;
            margin: 0 0 0 auto;
            page-break-inside: avoid;
        }
        #report-right-preview .report-doc .signature-section.signature-section--page-break {
            page-break-before: always;
            margin: 0 0 0 auto;
        }
        #report-right-preview .report-doc .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 0 !important;
        }
        #report-right-preview .report-doc .signature-cell {
            width: 33.333%;
            text-align: center;
            vertical-align: top;
            padding: 0 8px;
            border: 0 !important;
        }
        #report-right-preview .report-doc .signature-cell-empty { padding: 0; }
        #report-right-preview .report-doc .signature-label { font-size: 10px; }
        #report-right-preview .report-doc .signature-space { height: 34px; }
        #report-right-preview .report-doc .signature-line { border-top: 0.75px solid #000; margin: 0 8px 2px; }
        #report-right-preview .report-doc .signature-name { font-size: 10px; font-weight: 700; }
        #report-right-preview .report-doc .signature-role { font-size: 9.5px; color: #222; }

        #report-non-desktop-notice {
            display: block;
            margin: 0 0 12px 0;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #92400e;
            font-size: 12px;
            line-height: 1.4;
        }

        @media (min-width: 1280px) {
            #report-non-desktop-notice {
                display: none;
            }

            #report-builder-layout {
                display: block;
                padding-right: calc(min(48vw, 900px) + 24px);
                margin-top: 0;
            }

            #report-left-panel {
                width: auto;
                min-width: 640px;
                max-width: 760px;
                margin-top: -34px;
            }

            #report-right-preview {
                position: fixed;
                right: 1rem;
                top: 5rem;
                z-index: 1;
                width: min(48vw, 900px);
            }

            #report-right-preview-scroll {
                max-height: calc(100vh - 6rem);
                overflow: auto;
            }
        }
    </style>

    <div id="report-non-desktop-notice">
        Preview report paling akurat untuk layar desktop (>= 1280px). Di layar kecil, gunakan tombol <strong>Render PDF</strong> untuk hasil final ukuran A4.
    </div>

    <div id="report-builder-layout">
        <aside id="report-left-panel" style="max-width: 760px;">
            <div class="space-y-4 border border-gray-200 bg-white p-4 shadow-sm" style="border-radius: 1rem">
                {{ $this->form }}

                <div class="space-y-2 border-t border-gray-100 pt-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Pilih Task</label>
                        <span class="text-xs text-gray-500">{{ count($selectedTaskIds) }} dipilih</span>
                    </div>

                    <input
                        type="text"
                        wire:model.live.debounce.300ms="taskSearch"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        placeholder="Cari task atau PIC"
                    >

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

                    <div class="grid grid-cols-3 gap-2">
                        <x-filament::button size="xs" color="gray" wire:click="selectCurrentPageTasks">Pilih Halaman</x-filament::button>
                        <x-filament::button size="xs" color="gray" wire:click="selectFilteredTasks">Pilih Filter</x-filament::button>
                        <x-filament::button size="xs" color="danger" wire:click="unselectFilteredTasks">Hapus Filter</x-filament::button>
                    </div>

                    <div class="max-h-72 space-y-1 overflow-auto rounded-lg border border-gray-200 bg-gray-50/60 p-2">
                        @forelse ($availableTasks as $task)
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg bg-white px-2 py-2 hover:bg-gray-50" wire:key="task-picker-{{ $task->id }}">
                                <input type="checkbox" wire:model.live="selectedTaskIds" value="{{ (string) $task->id }}" class="mt-1 rounded border-gray-300">
                                <span class="min-w-0 flex-1 text-xs">
                                    <span class="flex items-start justify-between gap-2">
                                        <span class="truncate font-medium text-gray-900">{{ $task->task_name ?: '-' }}</span>
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ ((int) ($task->issues_count ?? 0)) > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ (int) ($task->issues_count ?? 0) }} issue
                                        </span>
                                    </span>
                                    <span class="block text-gray-500">{{ $task->staff?->name ?? '-' }} - {{ $task->project?->project_name ?? '-' }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="px-2 py-1 text-xs text-gray-500">Task tidak ditemukan.</p>
                        @endforelse
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-2 py-2 text-xs">
                        <span class="text-gray-600">
                            {{ $taskPickerMeta['from'] ?? 0 }}-{{ $taskPickerMeta['to'] ?? 0 }} / {{ $taskPickerMeta['total'] ?? 0 }}
                        </span>
                        <div class="flex items-center gap-1">
                            <x-filament::button size="xs" color="gray" wire:click="previousTaskPickerPage" :disabled="($taskPickerMeta['page'] ?? 1) <= 1">Prev</x-filament::button>
                            <span class="px-1 text-gray-600">{{ $taskPickerMeta['page'] ?? 1 }}/{{ $taskPickerMeta['last_page'] ?? 1 }}</span>
                            <x-filament::button size="xs" color="gray" wire:click="nextTaskPickerPage" :disabled="($taskPickerMeta['page'] ?? 1) >= ($taskPickerMeta['last_page'] ?? 1)">Next</x-filament::button>
                        </div>
                    </div>

                    <x-filament::button size="xs" color="danger" outlined wire:click="clearSelectedTasks" class="w-full">
                        Clear Semua Pilihan
                    </x-filament::button>
                </div>

                <div class="space-y-2 border-t border-gray-100 pt-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Pilih Issue</label>
                        <span class="text-xs text-gray-500">{{ count($selectedIssueIds) }} dipilih</span>
                    </div>

                    <input
                        type="text"
                        wire:model.live.debounce.300ms="issueSearch"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        placeholder="Cari issue, deskripsi, task, PIC"
                        @disabled(count($selectedTaskIds) === 0)
                    >

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <select wire:model.live="issueFilterStatus" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs" @disabled(count($selectedTaskIds) === 0)>
                            <option value="">All Issue Status</option>
                            @foreach ($issueStatusOptions as $status)
                                <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="issueFilterPriority" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs" @disabled(count($selectedTaskIds) === 0)>
                            <option value="">All Priority</option>
                            @foreach ($issuePriorityOptions as $priority)
                                <option value="{{ $priority }}">{{ ucfirst(str_replace('_', ' ', $priority)) }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="issueFilterStaffId" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs" @disabled(count($selectedTaskIds) === 0)>
                            <option value="">All PIC Issue</option>
                            @foreach ($issueStaffOptions as $staffId => $staffName)
                                <option value="{{ $staffId }}">{{ $staffName }}</option>
                            @endforeach
                        </select>
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-2 py-2 text-xs text-gray-700">
                            <input type="checkbox" wire:model.live="showOnlySelectedIssues" class="rounded border-gray-300" @disabled(count($selectedTaskIds) === 0)>
                            Show selected only
                        </label>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <x-filament::button size="xs" color="gray" wire:click="selectCurrentPageIssues" :disabled="count($selectedTaskIds) === 0">Pilih Halaman</x-filament::button>
                        <x-filament::button size="xs" color="gray" wire:click="selectFilteredIssues" :disabled="count($selectedTaskIds) === 0">Pilih Filter</x-filament::button>
                        <x-filament::button size="xs" color="danger" wire:click="unselectFilteredIssues" :disabled="count($selectedTaskIds) === 0">Hapus Filter</x-filament::button>
                    </div>

                    <div class="max-h-64 space-y-1 overflow-auto rounded-lg border border-gray-200 bg-gray-50/60 p-2">
                        @if (count($selectedTaskIds) === 0)
                            <p class="px-2 py-1 text-xs text-gray-500">Pilih task terlebih dahulu untuk menampilkan issue.</p>
                        @else
                            @forelse ($availableIssues as $issue)
                                <label class="flex cursor-pointer items-start gap-2 rounded-lg bg-white px-2 py-2 hover:bg-gray-50" wire:key="issue-picker-{{ $issue->id }}">
                                    <input type="checkbox" wire:model.live="selectedIssueIds" value="{{ (string) $issue->id }}" class="mt-1 rounded border-gray-300">
                                    <span class="min-w-0 flex-1 text-xs">
                                        <span class="flex items-start justify-between gap-2">
                                            <span class="truncate font-medium text-gray-900">{{ $issue->issue_name ?: '-' }}</span>
                                            <span class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold text-sky-800">
                                                Task: {{ $issue->task?->task_name ?? '-' }}
                                            </span>
                                        </span>
                                        <span class="mt-0.5 block text-gray-500">
                                            PIC: {{ $issue->staff?->name ?? '-' }}
                                            <span class="mx-1 text-gray-300">|</span>
                                            Status: {{ ucfirst(str_replace('_', ' ', (string) ($issue->status ?? '-'))) }}
                                            <span class="mx-1 text-gray-300">|</span>
                                            Priority: {{ ucfirst(str_replace('_', ' ', (string) ($issue->priority ?? '-'))) }}
                                        </span>
                                    </span>
                                </label>
                            @empty
                                <p class="px-2 py-1 text-xs text-gray-500">Issue tidak ditemukan untuk task yang dipilih.</p>
                            @endforelse
                        @endif
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-2 py-2 text-xs">
                        <span class="text-gray-600">
                            {{ $issuePickerMeta['from'] ?? 0 }}-{{ $issuePickerMeta['to'] ?? 0 }} / {{ $issuePickerMeta['total'] ?? 0 }}
                        </span>
                        <div class="flex items-center gap-1">
                            <x-filament::button size="xs" color="gray" wire:click="previousIssuePickerPage" :disabled="($issuePickerMeta['page'] ?? 1) <= 1">Prev</x-filament::button>
                            <span class="px-1 text-gray-600">{{ $issuePickerMeta['page'] ?? 1 }}/{{ $issuePickerMeta['last_page'] ?? 1 }}</span>
                            <x-filament::button size="xs" color="gray" wire:click="nextIssuePickerPage" :disabled="($issuePickerMeta['page'] ?? 1) >= ($issuePickerMeta['last_page'] ?? 1)">Next</x-filament::button>
                        </div>
                    </div>

                    <x-filament::button size="xs" color="danger" outlined wire:click="clearSelectedIssues" class="w-full" :disabled="count($selectedTaskIds) === 0">
                        Clear Semua Issue Terpilih
                    </x-filament::button>
                </div>
            </div>
        </aside>

        <section id="report-right-preview" class="min-w-0" style="margin-top: 0;">
            <div class="mb-2 flex justify-end gap-2">
                <x-filament::button
                    size="sm"
                    color="gray"
                    icon="heroicon-o-document-text"
                    disabled
                    x-tooltip="{ content: 'Sementara dinonaktifkan' }"
                >
                    Render DOCX
                </x-filament::button>
                <x-filament::button size="sm" icon="heroicon-o-document-arrow-down" wire:click="mountAction('renderPdf')">
                    Render PDF
                </x-filament::button>
            </div>
            <div id="report-right-preview-scroll" class="overflow-auto"
                style="padding: 14px; border: 1px solid #d9dee7; border-radius: 14px; background: #edf1f6; box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06);">
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
</x-filament-panels::page>
