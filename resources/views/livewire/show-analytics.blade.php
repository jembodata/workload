@php
    $rangeButtonBase = 'px-4 py-2 rounded-lg font-semibold transition-all duration-150 border';
    $rangeButtonActive = 'bg-white text-gray-900 border-gray-300';
    $rangeButtonInactive = 'bg-gray-200 text-gray-800 hover:bg-gray-300 border-gray-300';

    $dates = $resourcesData['dates'] ?? [];
    $rows = $resourcesData['rows'] ?? [];
    $resourceKpis = $kpis ?? [];
    $trend = $resourceKpis['trend'] ?? [];

    $tasksDelta = (float) ($trend['tasks_delta_pct'] ?? 0);
    $demandDelta = (float) ($trend['demand_delta_pct'] ?? 0);
    $overloadDelta = (float) ($trend['overload_delta_pct'] ?? 0);

    $deltaClass = static fn(float $value): string => $value > 0 ? 'text-red-600' : ($value < 0 ? 'text-emerald-600' : 'text-gray-500');
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
    </div>

    <div class="mb-4" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.75rem;">
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Total Tasks</p>
            <p class="text-xl font-semibold text-gray-900">{{ $resourceKpis['total_tasks'] ?? 0 }}</p>
            <p class="text-xs {{ $deltaClass($tasksDelta) }}">{{ $tasksDelta >= 0 ? '+' : '' }}{{ $tasksDelta }}% vs periode sebelumnya</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">PIC Aktif</p>
            <p class="text-xl font-semibold text-gray-900">{{ $resourceKpis['active_pic'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Demand Hours</p>
            <p class="text-xl font-semibold text-gray-900">{{ $resourceKpis['demand_hours'] ?? 0 }}</p>
            <p class="text-xs {{ $deltaClass($demandDelta) }}">{{ $demandDelta >= 0 ? '+' : '' }}{{ $demandDelta }}% vs periode sebelumnya</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Capacity Hours</p>
            <p class="text-xl font-semibold text-gray-900">{{ $resourceKpis['capacity_hours'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Utilization</p>
            <p class="text-xl font-semibold {{ ($resourceKpis['utilization_pct'] ?? 0) > 100 ? 'text-red-600' : 'text-gray-900' }}">{{ $resourceKpis['utilization_pct'] ?? 0 }}%</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-500">Overload Cells</p>
            <p class="text-xl font-semibold text-red-600">{{ $resourceKpis['overload_cells'] ?? 0 }}</p>
            <p class="text-xs {{ $deltaClass($overloadDelta) }}">{{ $overloadDelta >= 0 ? '+' : '' }}{{ $overloadDelta }}% vs periode sebelumnya</p>
        </div>
    </div>

    <div class="mb-4 w-full">{{ $this->form }}</div>

    <div class="overflow-x-auto">
        <table class="w-full table-auto border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-2 py-1 text-left">PIC</th>
                    <th class="border px-2 py-1 text-center">Demand</th>
                    <th class="border px-2 py-1 text-center">Capacity</th>
                    <th class="border px-2 py-1 text-center">Utilization</th>
                    <th class="border px-2 py-1 text-center">Overload Days</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $overloadDays = collect($row['days'])->filter(fn($hours) => (int) $hours > 8)->count();
                    @endphp
                    <tr>
                        <td class="border px-2 py-1">{{ $row['name'] }}</td>
                        <td class="border px-2 py-1 text-center font-semibold">{{ $row['demand_hours'] }}</td>
                        <td class="border px-2 py-1 text-center">{{ $row['capacity_hours'] }}</td>
                        <td class="border px-2 py-1 text-center">
                            <span class="rounded px-2 py-1 text-xs font-semibold {{ $row['utilization_pct'] > 100 ? 'bg-red-100 text-red-700' : ($row['utilization_pct'] >= 80 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                {{ $row['utilization_pct'] }}%
                            </span>
                        </td>
                        <td class="border px-2 py-1 text-center">{{ $overloadDays }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border px-3 py-4 text-center text-gray-500">Tidak ada data analitik pada filter saat ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
