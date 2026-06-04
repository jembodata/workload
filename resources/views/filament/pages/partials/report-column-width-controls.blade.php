@php
    $columnWidthMap = [
        'no' => 'No',
        'project_task' => 'Project & Task',
        'percent' => '%',
        'issue' => 'Issue',
        'action_plan' => 'Action Plan',
        'pic' => 'PIC',
        'evaluasi' => 'Evaluasi Efektivitas',
    ];
    $columnWidths = $columnWidths ?? [
        'no' => 4,
        'project_task' => 14,
        'percent' => 16,
        'issue' => 36,
        'action_plan' => 10,
        'pic' => 10,
        'evaluasi' => 10,
    ];
    $columnWidthTotal = array_sum(array_map(fn($v) => (float) $v, $columnWidths));
@endphp

<div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
        <span class="font-medium text-gray-700">Atur proporsi kolom tabel report.</span>
        <span class="text-gray-600">Total: <span class="font-semibold">{{ number_format($columnWidthTotal, 2) }}%</span></span>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        @foreach ($columnWidthMap as $colKey => $colLabel)
            <label class="rounded-lg border border-gray-200 bg-white px-2 py-2">
                <div class="mb-1 flex items-center justify-between gap-2 text-xs">
                    <span class="font-medium text-gray-700">{{ $colLabel }}</span>
                    <span class="text-gray-500">{{ number_format((float) ($columnWidths[$colKey] ?? 0), 2) }}%</span>
                </div>
                <input
                    type="range"
                    min="1"
                    max="100"
                    step="0.1"
                    wire:model.live.debounce.80ms="formData.column_widths.{{ $colKey }}"
                    class="w-full cursor-pointer"
                />
            </label>
        @endforeach
    </div>

    <div class="flex justify-end">
        <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-path" wire:click="resetColumnWidths">
            Reset Lebar Default
        </x-filament::button>
    </div>
</div>
