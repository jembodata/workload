@push('styles')
<style>
    .gantt_resource_marker_ok {
        background-color: #22c55e;
        color: white;
    }

    .gantt_resource_marker_overtime {
        background-color: #ef4444;
        color: white;
    }

    .today-header {
        background-color: #3b82f6;
        color: white;
        font-weight: bold;
    }

    .weekend-header {
        background-color: #facc15;
        font-weight: bold;
    }

    .weekend-cell {
        background-color: #fef9c3;
    }
</style>
@endpush

@php
    $rangeButtonBase = 'px-4 py-2 rounded-lg font-semibold transition-all duration-150 border';
    $rangeButtonActive = 'bg-white text-gray-900 border-gray-300';
    $rangeButtonInactive = 'bg-gray-200 text-gray-800 hover:bg-gray-300 border-gray-300';

    $dates = $resourcesData['dates'] ?? [];
    $rows = $resourcesData['rows'] ?? [];
@endphp

<div class="flex flex-col gap-1">
    <x-filament-actions::modals />

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

        <div class="ml-4 flex items-center gap-4 text-sm">
            <div class="flex items-center gap-1">
                <div class="gantt_resource_marker_ok h-4 w-4 border border-gray-400"></div>
                Normal (&lt;= 8 jam)
            </div>
            <div class="flex items-center gap-1">
                <div class="gantt_resource_marker_overtime h-4 w-4 border border-gray-400"></div>
                Overload (&gt; 8 jam)
            </div>
            <div class="flex items-center gap-1">
                <div class="today-header h-4 w-4 border border-gray-400"></div>
                Today
            </div>
            <div class="flex items-center gap-1">
                <div class="weekend-header h-4 w-4 border border-gray-400"></div>
                Weekend
            </div>
        </div>
    </div>

    <div class="mb-4 w-full">
        {{ $this->form }}
    </div>

    <div class="overflow-x-auto">
        <table class="w-full table-auto border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-2 py-1 text-left">Name</th>
                    <th class="border px-2 py-1 text-center">Total</th>
                    @foreach ($dates as $date)
                        @php
                            $carbonDate = \Carbon\Carbon::parse($date);
                            $isToday = $date === now()->format('Y-m-d');
                            $isWeekend = $carbonDate->isWeekend();
                        @endphp
                        <th class="border px-2 py-1 text-center {{ $isToday ? 'today-header' : '' }} {{ $isWeekend ? 'weekend-header' : '' }}">
                            {{ $carbonDate->format('d M') }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="border px-2 py-1">{{ $row['name'] }}</td>
                        <td class="border px-2 py-1 text-center font-semibold">{{ $row['demand_hours'] }}</td>
                        @foreach ($dates as $date)
                            @php
                                $hours = $row['days'][$date] ?? 0;
                                $cellClass = $hours > 8
                                    ? 'gantt_resource_marker_overtime'
                                    : ($hours > 0 ? 'gantt_resource_marker_ok' : '');
                                $isWeekend = \Carbon\Carbon::parse($date)->isWeekend();
                            @endphp
                            <td class="border px-2 py-1 text-center {{ $cellClass }} {{ $isWeekend ? 'weekend-cell' : '' }}">
                                @if ($hours > 8)
                                    <button
                                        type="button"
                                        wire:click="openOverloadDetails({{ (int) $row['staff_id'] }}, '{{ $date }}')"
                                        class="font-semibold underline underline-offset-2"
                                        title="Lihat detail overload"
                                    >
                                        {{ $hours }}
                                    </button>
                                @else
                                    {{ $hours > 0 ? $hours : '' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + count($dates) }}" class="border px-3 py-4 text-center text-gray-500">
                            Tidak ada data pada filter saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
