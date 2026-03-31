<div style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000;">
    <table style="width:100%; border-collapse: collapse; border:1px solid #000;">
        <tr>
            <td style="width:18%; border:1px solid #000; text-align:center; vertical-align:middle; padding:6px;">
                <img src="{{ $logoSrc }}" alt="Logo" style="width:88px; height:44px;" />
            </td>
            <td style="border:1px solid #000; text-align:center; vertical-align:middle;">
                <div style="font-size:14px; font-weight:700;">{{ $titleId ?: '-' }}</div>
                <div style="font-size:11px; font-style:italic; font-weight:700; color:#0054a6; margin-top:2px;">{{ $titleEn ?: '-' }}</div>
            </td>
            <td style="width:25%; border:1px solid #000; padding:0;">
                <table style="width:100%; border-collapse: collapse;">
                    <tr>
                        <td style="width:45%; border:1px solid #000; padding:2px 5px; font-weight:700;">No. Document</td>
                        <td style="border:1px solid #000; padding:2px 5px;">{{ $documentNo ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="width:45%; border:1px solid #000; padding:2px 5px; font-weight:700;">Effective date</td>
                        <td style="border:1px solid #000; padding:2px 5px;">{{ $effectiveDate ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="width:45%; border:1px solid #000; padding:2px 5px; font-weight:700;">Revision</td>
                        <td style="border:1px solid #000; padding:2px 5px;">{{ $revision ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="width:45%; border:1px solid #000; padding:2px 5px; font-weight:700;">Page</td>
                        <td style="border:1px solid #000; padding:2px 5px;">{{ $pageLabel ?: '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="width:100%; border-collapse: collapse; border-left:1px solid #000; border-right:1px solid #000;">
        <tr>
            <td rowspan="5" style="width:18%; border:1px solid #000;"></td>
            <td style="width:15%; border:1px solid #000; padding:3px 6px; font-weight:700;">Hadir</td>
            <td style="border:1px solid #000; padding:3px 6px;">: {{ $meetingPresent ?: '-' }}</td>
        </tr>
        <tr>
            <td style="width:15%; border:1px solid #000; padding:3px 6px; font-weight:700;">Absen</td>
            <td style="border:1px solid #000; padding:3px 6px;">: {{ $meetingAbsent ?: '-' }}</td>
        </tr>
        <tr>
            <td style="width:15%; border:1px solid #000; padding:3px 6px; font-weight:700;">Hari</td>
            <td style="border:1px solid #000; padding:3px 6px;">: {{ $meetingDay ?: '-' }}</td>
        </tr>
        <tr>
            <td style="width:15%; border:1px solid #000; padding:3px 6px; font-weight:700;">Waktu</td>
            <td style="border:1px solid #000; padding:3px 6px;">: {{ $meetingTime ?: '-' }}</td>
        </tr>
        <tr>
            <td style="width:15%; border:1px solid #000; padding:3px 6px; font-weight:700;">Tempat</td>
            <td style="border:1px solid #000; padding:3px 6px;">: {{ $meetingPlace ?: '-' }}</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse: collapse; table-layout: fixed; border:1px solid #000;">
        <thead>
            <tr>
                <th style="width:4%; border:1px solid #000; padding:4px; text-align:center;">No</th>
                <th style="width:10%; border:1px solid #000; padding:4px; text-align:center;">Item</th>
                <th style="width:20%; border:1px solid #000; padding:4px; text-align:center;">Pembahasan (Input)</th>
                <th style="width:36%; border:1px solid #000; padding:4px; text-align:center;">Rencana Tindakan (Output)</th>
                <th style="width:10%; border:1px solid #000; padding:4px; text-align:center;">Target</th>
                <th style="width:10%; border:1px solid #000; padding:4px; text-align:center;">PIC</th>
                <th style="width:10%; border:1px solid #000; padding:4px; text-align:center;">Evaluasi Efektivitas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($previewRows as $row)
                @php
                    $taskStatusKey = strtolower((string) ($row['task_status_key'] ?? $row['evaluasi'] ?? ''));
                    $taskEvaluasiText = (string) ($row['task_evaluasi'] ?? ucfirst(str_replace('_', ' ', (string) ($row['evaluasi'] ?? 'TBD'))));
                    $issueStatusKey = strtolower((string) ($row['issue_status_key'] ?? 'tbd'));
                    $issueEvaluasiText = (string) ($row['issue_evaluasi'] ?? '-');
                    $hasIssueEvaluasi = trim($issueEvaluasiText) !== '' && trim($issueEvaluasiText) !== '-';
                    $issueEvaluasiItems = collect($row['issue_evaluasi_items'] ?? [])->filter(fn($item) => is_array($item));
                    $taskColor = match ($taskStatusKey) {
                        'closed' => '#166534',
                        'progress' => '#a16207',
                        'opened', 'open' => '#1d4ed8',
                        'overdue' => '#b91c1c',
                        'postponed' => '#4b5563',
                        default => '#111827',
                    };
                    $issueColor = match ($issueStatusKey) {
                        'closed' => '#166534',
                        'progress' => '#a16207',
                        'opened', 'open' => '#1d4ed8',
                        'overdue' => '#b91c1c',
                        'postponed' => '#4b5563',
                        default => '#111827',
                    };
                @endphp
                <tr>
                    <td style="border:1px solid #000; padding:4px; text-align:center; vertical-align:top;">{{ $row['no'] }}</td>
                    <td style="border:1px solid #000; padding:4px; text-align:center; vertical-align:top;">{{ $row['item'] }}</td>
                    <td style="border:1px solid #000; padding:4px; vertical-align:top;">{!! nl2br(e($row['input'])) !!}</td>
                    <td style="border:1px solid #000; padding:4px; vertical-align:top;">{!! nl2br(e($row['output'])) !!}</td>
                    <td style="border:1px solid #000; padding:4px; text-align:center; vertical-align:top;">{{ $row['target'] }}</td>
                    <td style="border:1px solid #000; padding:4px; text-align:center; vertical-align:top;">{{ $row['pic'] }}</td>
                    <td style="border:1px solid #000; padding:4px; vertical-align:top; text-align:center;">
                        <div style="font-weight:700; color:{{ $taskColor }};">{{ $taskEvaluasiText }}</div>
                        @if ($hasIssueEvaluasi)
                            <div style="margin-top:8px;">
                                @if ($issueEvaluasiItems->isNotEmpty())
                                    @foreach ($issueEvaluasiItems as $issueItem)
                                        @php
                                            $itemKey = strtolower((string) ($issueItem['key'] ?? 'tbd'));
                                            $itemLabel = (string) ($issueItem['label'] ?? 'TBD');
                                            $itemColor = match ($itemKey) {
                                                'closed' => '#166534',
                                                'progress' => '#a16207',
                                                'opened', 'open' => '#1d4ed8',
                                                'overdue' => '#b91c1c',
                                                'postponed' => '#4b5563',
                                                default => '#111827',
                                            };
                                        @endphp
                                        <div style="color:{{ $itemColor }};">{{ $itemLabel }}</div>
                                    @endforeach
                                @else
                                    <div style="color:{{ $issueColor }};">{{ $issueEvaluasiText }}</div>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="border:1px solid #000; padding:8px; text-align:center; color:#6b7280;">Belum ada task dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
