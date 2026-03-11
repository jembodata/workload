<div class="w-full"> {{-- Memastikan container luar mengambil 100% lebar --}}
    <div class="overflow-x-auto border border-gray-200 rounded-lg dark:border-white/10 w-full">
        <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5 table-fixed"> {{-- table-fixed membantu kontrol lebar kolom --}}
            <thead class="bg-gray-50/50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-900 uppercase dark:text-white w-[15%]">Tanggal</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-900 uppercase dark:text-white w-[55%]">Kendala / Masalah</th>
                    <th class="px-4 py-3 text-xs font-semibold text-center text-gray-900 uppercase dark:text-white w-[15%]">Severity</th>
                    <th class="px-4 py-3 text-xs font-semibold text-center text-gray-900 uppercase dark:text-white w-[15%]">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-transparent">
                @forelse($getRecord()->issues as $issue)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($issue->tanggal)->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-900 dark:text-white break-words">{{ $issue->issue_name }}</div>
                            @if($issue->description)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 break-words line-clamp-2">{{ $issue->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            {{-- Badge Severity --}}
                            @php
                                $severityColor = match($issue->severity) {
                                    'critical' => 'text-danger-700 bg-danger-500/10 ring-danger-600/20',
                                    'major' => 'text-warning-700 bg-warning-500/10 ring-warning-600/20',
                                    default => 'text-primary-700 bg-primary-500/10 ring-primary-600/20',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-md ring-1 ring-inset {{ $severityColor }}">
                                {{ ucfirst($issue->severity) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            {{-- Badge Status --}}
                            @php
                                $statusColor = $issue->status === 'open' 
                                    ? 'text-danger-600 bg-danger-500/10 ring-danger-600/20' 
                                    : 'text-success-600 bg-success-500/10 ring-success-600/20';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-md ring-1 ring-inset {{ $statusColor }}">
                                {{ ucfirst($issue->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    {{-- Empty state --}}
                @endforelse
            </tbody>
        </table>
    </div>
</div>