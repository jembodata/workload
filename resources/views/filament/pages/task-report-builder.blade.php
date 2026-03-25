<x-filament-panels::page>
    <x-filament-actions::modals />

    <style>
        #report-builder-layout {
            display: block;
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

        @media (min-width: 1280px) {
            #report-builder-layout {
                padding-right: calc(min(54vw, 1020px) + 24px);
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
                z-index: 20;
                width: min(54vw, 1020px);
            }

            #report-right-preview-scroll {
                max-height: calc(100vh - 6rem);
                overflow: auto;
            }
        }
    </style>

    <div id="report-builder-layout">
        <aside id="report-left-panel" style="max-width: 760px;">
            <div class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
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
                                <span class="text-xs">
                                    <span class="font-medium text-gray-900">{{ $task->task_name ?: '-' }}</span>
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
                                    <span class="text-xs">
                                        <span class="font-medium text-gray-900">{{ $issue->issue_name ?: '-' }}</span>
                                        <span class="block text-gray-500">{{ $issue->task?->task_name ?? '-' }} - {{ $issue->staff?->name ?? '-' }}</span>
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
            <div class="mb-2 flex justify-end">
                <x-filament::button size="sm" icon="heroicon-o-document-arrow-down" wire:click="mountAction('renderPdf')">
                    Render PDF
                </x-filament::button>
            </div>
            <div id="report-right-preview-scroll" class="overflow-auto rounded-2xl border border-gray-200 bg-gray-100 p-4 shadow-inner">
                <div class="mx-auto w-[210mm] min-h-[297mm] max-w-none bg-white p-[12mm] shadow-sm">
                    <table class="w-full border-collapse text-[14px] leading-tight">
                        <tbody>
                            <tr class="h-[112px]">
                                <td class="w-[150px] border border-gray-700 p-2 align-middle text-center">
                                    <div class="mx-auto h-[54px] w-[110px] overflow-hidden">
                                        <img src="{{ $logoSrc ?? asset('images/logo_report.png') }}" alt="Logo" class="h-11 object-scale-down">
                                    </div>
                                </td>
                                <td class="border border-gray-700 p-2 align-middle">
                                    <div class="flex h-full min-h-[96px] flex-col items-center justify-center text-center">
                                        <div class="text-[30px] font-bold tracking-tight leading-tight">{{ $titleId ?: '-' }}</div>
                                        <div class="mt-1 text-[18px] font-semibold italic text-sky-600 leading-tight">{{ $titleEn ?: '-' }}</div>
                                    </div>
                                </td>
                                <td class="w-[300px] border border-gray-700 p-0 align-top">
                                    <table class="w-full border-collapse text-[14px] leading-tight">
                                        <tr>
                                            <td class="w-[58%] border border-gray-700 px-2 py-1 font-semibold whitespace-nowrap">No. Document</td>
                                            <td class="border border-gray-700 px-2 py-1">{{ $documentNo ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="w-[58%] border border-gray-700 px-2 py-1 font-semibold whitespace-nowrap">Effective date</td>
                                            <td class="border border-gray-700 px-2 py-1">{{ $effectiveDate ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="w-[58%] border border-gray-700 px-2 py-1 font-semibold whitespace-nowrap">Revision</td>
                                            <td class="border border-gray-700 px-2 py-1">{{ $revision ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="w-[58%] border border-gray-700 px-2 py-1 font-semibold whitespace-nowrap">Page</td>
                                            <td class="border border-gray-700 px-2 py-1 whitespace-nowrap">{{ $pageLabel ?: '-' }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr><td class="border border-gray-700 px-2 py-1 text-left font-semibold">Hadir</td><td colspan="2" class="border border-gray-700 px-2 py-1">: {{ $meetingPresent ?: '-' }}</td></tr>
                            <tr><td class="border border-gray-700 px-2 py-1 text-left font-semibold">Absen</td><td colspan="2" class="border border-gray-700 px-2 py-1">: {{ $meetingAbsent ?: '-' }}</td></tr>
                            <tr><td class="border border-gray-700 px-2 py-1 text-left font-semibold">Hari</td><td colspan="2" class="border border-gray-700 px-2 py-1">: {{ $meetingDay ?: '-' }}</td></tr>
                            <tr><td class="border border-gray-700 px-2 py-1 text-left font-semibold">Waktu</td><td colspan="2" class="border border-gray-700 px-2 py-1">: {{ $meetingTime ?: '-' }}</td></tr>
                            <tr><td class="border border-gray-700 px-2 py-1 text-left font-semibold">Tempat</td><td colspan="2" class="border border-gray-700 px-2 py-1">: {{ $meetingPlace ?: '-' }}</td></tr>
                        </tbody>
                    </table>

                    <table class="w-full border-collapse text-[14px] leading-tight">
                        <colgroup>
                            <col style="width: 4%">
                            <col style="width: 12%">
                            <col style="width: 15%">
                            <col style="width: 39%">
                            <col style="width: 10%">
                            <col style="width: 9%">
                            <col style="width: 11%">
                        </colgroup>
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-700 p-1 text-center font-bold">No</th>
                                <th class="border border-gray-700 p-1 text-center font-bold">Item</th>
                                <th class="border border-gray-700 p-1 text-center font-bold">Pembahasan<br>(Input)</th>
                                <th class="border border-gray-700 p-1 text-center font-bold">Rencana Tindakan (Output)</th>
                                <th class="border border-gray-700 p-1 text-center font-bold">Target</th>
                                <th class="border border-gray-700 p-1 text-center font-bold">PIC</th>
                                <th class="border border-gray-700 p-1 text-center font-bold">Evaluasi<br>Efektivitas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($previewRows as $row)
                                @php
                                    $evaluasiRaw = strtolower((string) ($row['evaluasi'] ?? ''));
                                    $evaluasiBg = match ($evaluasiRaw) {
                                        'closed' => 'bg-emerald-300',
                                        'progress', 'opened', 'open' => 'bg-red-500',
                                        default => 'bg-amber-300',
                                    };
                                    $evaluasiText = $evaluasiRaw === 'closed' ? 'Closed' : ($evaluasiRaw === 'progress' || $evaluasiRaw === 'opened' || $evaluasiRaw === 'open' ? 'Open' : 'TBD');
                                @endphp
                                <tr>
                                    <td class="border border-gray-700 p-1 text-center align-top">{{ $row['no'] }}</td>
                                    <td class="border border-gray-700 p-1 text-center align-top">{{ $row['item'] }}</td>
                                    <td class="border border-gray-700 p-1 align-top">{{ $row['input'] }}</td>
                                    <td class="border border-gray-700 p-1 align-top">{!! nl2br(e($row['output'])) !!}</td>
                                    <td class="border border-gray-700 p-1 text-center align-top">{{ $row['target'] }}</td>
                                    <td class="border border-gray-700 p-1 text-center align-top">{{ $row['pic'] }}</td>
                                    <td class="border border-gray-700 p-1 text-center align-top font-semibold {{ $evaluasiBg }}">{{ $evaluasiText }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="border border-gray-700 p-4 text-center text-gray-500">
                                        Belum ada task dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
