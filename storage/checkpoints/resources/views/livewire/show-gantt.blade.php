@push('styles')
<link defer href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css" rel="stylesheet">
<style>
    .weekend-header {
        background-color: #facc15;
        font-weight: bold;
    }

    .weekend-cell {
        background-color: #fef9c3;
    }

    :root {
        --dhx-gantt-font-size: 13px;
    }

    .overdue {
        background-color: #fee2e2 !important;
    }

    .today {
        background-color: blue;
    }
</style>
@endpush

@php
    $rangeButtonBase = 'px-4 py-2 rounded-lg font-semibold transition-all duration-150 border';
    $rangeButtonActive = 'bg-white text-gray-900 border-gray-300';
    $rangeButtonInactive = 'bg-gray-200 text-gray-800 hover:bg-gray-300 border-gray-300';
    $ganttKpis = $kpis ?? [];
@endphp

<div class="flex flex-col gap-1">
    <div class="mb-4 flex items-center gap-2">
        <button wire:click="previousRange" class="rounded-lg border border-gray-300 bg-gray-200 px-3 py-2 text-gray-800 hover:bg-gray-300">
            &larr;
        </button>

        <button wire:click="setRange('weekly')" class="{{ $rangeButtonBase }} {{ $range_type === 'weekly' ? $rangeButtonActive : $rangeButtonInactive }}">
            Weekly
        </button>

        <button wire:click="setRange('monthly')" class="{{ $rangeButtonBase }} {{ $range_type === 'monthly' ? $rangeButtonActive : $rangeButtonInactive }}">
            Monthly
        </button>

        <button wire:click="nextRange" class="rounded-lg border border-gray-300 bg-gray-200 px-3 py-2 text-gray-800 hover:bg-gray-300">
            &rarr;
        </button>

        <button
            wire:click="resetFilters"
            type="button"
            title="Reset Filter"
            aria-label="Reset Filter"
            class="rounded-lg border border-gray-300 bg-gray-200 p-2 text-gray-700 hover:bg-gray-300"
        >
            <x-heroicon-o-arrow-path class="h-5 w-5" />
        </button>

        <div class="ml-6 flex items-center gap-4">
            <span class="font-semibold text-gray-700">Display tasks with priority:</span>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="urgent" class="rounded" checked>
                Urgent
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="high" class="rounded" checked>
                High
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="medium" class="rounded" checked>
                Medium
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="low" class="rounded" checked>
                Low
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="not_priority" class="rounded" checked>
                Not Priority
            </label>
        </div>
    </div>

    <div class="mb-4" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.75rem;">
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Total Tasks</p>
            <p class="text-xl font-semibold text-gray-900">{{ $ganttKpis['total_tasks'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">PIC Aktif</p>
            <p class="text-xl font-semibold text-gray-900">{{ $ganttKpis['active_pic'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Overdue Tasks</p>
            <p class="text-xl font-semibold text-red-600">{{ $ganttKpis['overdue_tasks'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Planned Hours</p>
            <p class="text-xl font-semibold text-gray-900">{{ $ganttKpis['planned_hours'] ?? 0 }}</p>
        </div>
    </div>

    <div class="mb-4 w-full">{{ $this->form }}</div>
    <x-filament-actions::modals />

    <div class="w-full" wire:ignore>
        <div id="gantt_here" style="width: 100%; height: 700px;"></div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>
<script>
    let ganttPageInitialized = false;
    let todayMarkerId = null;
    let ganttData = @json($ganttData ?? ['data' => [], 'links' => []]);

    function ensureTodayMarker() {
        if (todayMarkerId !== null) {
            const existingMarker = gantt.getMarker(todayMarkerId);
            if (existingMarker) {
                existingMarker.start_date = new Date();
                gantt.updateMarker(todayMarkerId);
                return;
            }
        }

        todayMarkerId = gantt.addMarker({
            start_date: new Date(),
            css: 'today',
            text: 'Today',
            title: 'Hari ini',
        });
    }

    function initGanttConfig(startDate, endDate) {
        gantt.plugins({
            marker: true,
            quick_info: true,
        });

        gantt.config = {
            ...gantt.config,
            date_format: '%Y-%m-%d',
            readonly: true,
            grid_width: 350,
            row_height: 40,
            task_height: 32,
            bar_height: 24,
            work_time: true,
            start_date: new Date(startDate),
            end_date: new Date(endDate),
            columns: [
                {
                    name: 'text',
                    label: 'Task name',
                    width: 200,
                    tree: true,
                },
                {
                    name: 'start_date',
                    label: 'Start Date',
                    width: 120,
                    align: 'center',
                },
                {
                    name: 'duration',
                    label: 'Duration',
                    width: 60,
                    align: 'center',
                },
                {
                    name: 'priority',
                    label: 'Priority',
                    width: 90,
                    align: 'center',
                },
            ],
        };

        gantt.setWorkTime({ day: 0, hours: false });
        gantt.setWorkTime({ day: 6, hours: false });

        gantt.templates.task_color = (s, e, t) => t.color || null;
        gantt.templates.task_class = (s, e, t) => (t.is_overdue ? 'overdue' : '');
        gantt.templates.tooltip_text = (s, e, t) => `
            <b>Task:</b> ${t.text}<br/>
            <b>Duration:</b> ${t.duration} day(s)<br/>
            <b>Estimasi Jam:</b> ${t.estimasi_jam}<br/>
            <b>Start:</b> ${gantt.templates.tooltip_date_format(s)}<br/>
            <b>End:</b> ${gantt.templates.tooltip_date_format(e)}
            ${t.is_overdue ? '<br/><b style="color:#ef4444;">OVERDUE</b>' : ''}`;
        gantt.templates.scale_cell_class = (d) => (d.getDay() === 0 || d.getDay() === 6 ? 'weekend-header' : '');
        gantt.templates.timeline_cell_class = (t, d) => (d.getDay() === 0 || d.getDay() === 6 ? 'weekend-cell' : '');

        gantt.attachEvent('onBeforeTaskDisplay', function(id, task) {
            const filters = document.querySelectorAll("input[wire\\:model='priorityFilter']");
            if (!task.priority) return true;

            for (let i = 0; i < filters.length; i++) {
                const filter = filters[i];
                if (filter.checked && task.priority === filter.value) {
                    return true;
                }
            }

            return false;
        });

        document.querySelectorAll("input[wire\\:model='priorityFilter']").forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                gantt.render();
            });
        });
    }

    function renderGantt(data, startDate, endDate) {
        if (!ganttPageInitialized) {
            initGanttConfig(startDate, endDate);
            gantt.init('gantt_here');

            ensureTodayMarker();

            setInterval(() => {
                ensureTodayMarker();
            }, 60000);

            ganttPageInitialized = true;
        } else {
            gantt.config.start_date = new Date(startDate);
            gantt.config.end_date = new Date(endDate);
        }

        gantt.clearAll();
        gantt.parse(data || { data: [], links: [] });
        ensureTodayMarker();

        gantt.render();
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderGantt(ganttData, "{{ $start_date }}", "{{ $end_date }}");
    });

    document.addEventListener('livewire:init', () => {
        Livewire.on('refresh-gantt', (payload) => {
            ganttData = payload.ganttData;
            renderGantt(ganttData, payload.startDate, payload.endDate);
        });
    });
</script>
@endpush
