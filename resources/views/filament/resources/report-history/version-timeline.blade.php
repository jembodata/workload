@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\ReportHistory> $versions */
@endphp

<div class="space-y-3">
    @forelse ($versions as $history)
        <div class="rounded-xl border border-gray-200 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">
                        V{{ max(1, (int) $history->version_no) }}
                    </span>
                    <span class="text-gray-700">
                        {{ optional($history->printed_at)->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }}
                    </span>
                    <span class="text-gray-500">oleh {{ $history->user?->name ?? '-' }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @if (!empty($history->pdf_path))
                        <a
                            href="{{ route('task-report.history.pdf', ['history' => $history]) }}"
                            target="_blank"
                            class="inline-flex items-center rounded-md border border-gray-300 px-2 py-1 font-medium text-gray-700 hover:bg-gray-50"
                        >
                            View PDF
                        </a>
                    @endif

                    @if (!empty($history->docx_path))
                        <a
                            href="{{ route('task-report.history.docx', ['history' => $history]) }}"
                            target="_blank"
                            class="inline-flex items-center rounded-md border border-gray-300 px-2 py-1 font-medium text-gray-700 hover:bg-gray-50"
                        >
                            View DOCX
                        </a>
                    @endif

                    @if ((int) $history->printed_by === $ownerId)
                        <a
                            href="{{ \App\Filament\Pages\TaskReportBuilder::getUrl(['history_id' => $history->id]) }}"
                            class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-2 py-1 font-medium text-amber-800 hover:bg-amber-100"
                        >
                            Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500">
            Belum ada riwayat versi.
        </div>
    @endforelse
</div>
