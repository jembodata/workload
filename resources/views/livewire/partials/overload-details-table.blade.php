<div class="max-h-[60vh] overflow-auto">
    <table class="w-full table-auto border-collapse border border-gray-200 text-sm">
        <thead>
            <tr class="bg-gray-50">
                <th class="border px-2 py-1 text-left">Task</th>
                <th class="border px-2 py-1 text-left">Project</th>
                <th class="border px-2 py-1 text-center">Status</th>
                <th class="border px-2 py-1 text-center">Priority</th>
                <th class="border px-2 py-1 text-center">Jam</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($overloadDetails as $item)
                <tr>
                    <td class="border px-2 py-1">{{ $item['task_name'] }}</td>
                    <td class="border px-2 py-1">{{ $item['project_name'] }}</td>
                    <td class="border px-2 py-1 text-center">{{ ucfirst($item['status']) }}</td>
                    <td class="border px-2 py-1 text-center">{{ ucfirst(str_replace('_', ' ', $item['priority'])) }}</td>
                    <td class="border px-2 py-1 text-center font-semibold">{{ $item['hours'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="border px-3 py-4 text-center text-gray-500">Tidak ada task penyebab overload ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
