<div class="document-container">
    @if ($showCover ?? false)
        <table class="header-table">
            <colgroup>
                <col style="width: 18%;">
                <col style="width: 57%;">
                <col style="width: 25%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="logo-cell">
                        <div class="logo-wrap">
                            <img src="{{ $logoSrc }}" alt="Logo">
                        </div>
                    </td>
                    <td class="title-cell">
                        <div class="main-title">{{ $titleId ?: '-' }}</div>
                        <div class="sub-title">{{ $titleEn ?: '-' }}</div>
                    </td>
                    <td class="info-cell">
                        <table class="info-table">
                            <tr><td class="label">No. Document</td><td>{{ $documentNo ?: '-' }}</td></tr>
                            <tr><td class="label">Effective date</td><td>{{ $effectiveDate ?: '-' }}</td></tr>
                            <tr><td class="label">Revision</td><td>{{ $revision ?: '-' }}</td></tr>
                            <tr><td class="label">Page</td><td>{{ ($pageNumber ?? 1) . ' dari ' . ($totalPages ?? 1) }}</td></tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="details-table">
            <colgroup>
                <col style="width: 18%;">
                <col style="width: 82%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="detail-label">Hadir</td>
                    <td class="detail-value">: {{ $meetingPresent ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="detail-label">Absen</td>
                    <td class="detail-value">: {{ $meetingAbsent ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="detail-label">Hari</td>
                    <td class="detail-value">: {{ $meetingDay ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="detail-label">Waktu</td>
                    <td class="detail-value">: {{ $meetingTime ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="detail-label">Tempat</td>
                    <td class="detail-value">: {{ $meetingPlace ?: '-' }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <table class="data-table-section">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-item">Item</th>
                <th class="col-pembahasan">Pembahasan<br>(Input)</th>
                <th class="col-rencana">Rencana Tindakan (Output)</th>
                <th class="col-target">Target</th>
                <th class="col-pic">PIC</th>
                <th class="col-evaluasi">Evaluasi<br>Efektivitas</th>
            </tr>
        </thead>
        <tbody>
            @php
                $rows = collect($pageRows ?? [])->filter(fn($item) => is_array($item));
            @endphp

            @forelse ($rows as $row)
                @php
                    $taskStatusKey = strtolower((string) ($row['task_status_key'] ?? $row['evaluasi'] ?? ''));
                    $taskEvaluasiText = (string) ($row['task_evaluasi'] ?? ucfirst(str_replace('_', ' ', (string) ($row['evaluasi'] ?? 'TBD'))));
                    $taskEvaluasiClass = match ($taskStatusKey) {
                        'closed' => 'eval-closed',
                        'progress' => 'eval-progress',
                        'opened', 'open' => 'eval-open',
                        'overdue' => 'eval-overdue',
                        'postponed' => 'eval-postponed',
                        default => 'eval-tbd',
                    };
                    $issueEntries = collect($row['issue_entries'] ?? [])->filter(fn($item) => is_array($item));
                    $rowSpan = max(1, $issueEntries->count() + 1);
                @endphp
                <tr class="data-row">
                    <td class="col-no center" rowspan="{{ $rowSpan }}">{{ $row['no'] }}</td>
                    <td class="col-item center" rowspan="{{ $rowSpan }}">{{ $row['item'] }}</td>
                    <td class="col-pembahasan" rowspan="{{ $rowSpan }}">{!! nl2br(e($row['input'])) !!}</td>
                    <td class="col-rencana">{!! nl2br(e($row['output'])) !!}</td>
                    <td class="col-target center">{{ $row['target'] }}</td>
                    <td class="col-pic center">{{ $row['pic'] }}</td>
                    <td class="col-evaluasi">
                        <div class="eval-stack">
                            <div class="eval-pill {{ $taskEvaluasiClass }}">{{ $taskEvaluasiText }}</div>
                        </div>
                    </td>
                </tr>
                @foreach ($issueEntries as $issueEntry)
                    @php
                        $issueEntryKey = strtolower((string) ($issueEntry['status_key'] ?? 'tbd'));
                        $issueEntryLabel = (string) ($issueEntry['status_label'] ?? 'TBD');
                        $issueEntryClass = match ($issueEntryKey) {
                            'closed' => 'eval-closed',
                            'progress' => 'eval-progress',
                            'opened', 'open' => 'eval-open',
                            'overdue' => 'eval-overdue',
                            'postponed' => 'eval-postponed',
                            default => 'eval-tbd',
                        };
                    @endphp
                    <tr class="data-row">
                        <td class="col-rencana">- {!! nl2br(e((string) ($issueEntry['description'] ?? ''))) !!}</td>
                        <td class="col-target center"></td>
                        <td class="col-pic center">{{ (string) ($issueEntry['pic'] ?? '-') }}</td>
                        <td class="col-evaluasi">
                            <div class="eval-stack">
                                <div class="eval-pill {{ $issueEntryClass }}">{{ $issueEntryLabel }}</div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr class="data-row">
                    <td colspan="7" class="center empty-message">{{ ($showCover ?? false) ? 'Belum ada task dipilih.' : '' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (($showSignatures ?? false) && !empty($signatures ?? []))
        @php
            $signatureCells = collect($signatures ?? [])->values();
            while ($signatureCells->count() < 3) {
                $signatureCells->prepend(null);
            }
            $signaturePushPx = (int) ($signaturePushPx ?? 0);
        @endphp
        <div class="signature-section signature-section--flow" style="padding-top: {{ $signaturePushPx }}px;">
            <table class="signature-table">
                <tr>
                    @foreach ($signatureCells as $signature)
                        @if (is_array($signature))
                            <td class="signature-cell">
                                <div class="signature-label">Disetujui,</div>
                                <div class="signature-space"></div>
                                <div class="signature-line"></div>
                                <div class="signature-name">{{ $signature['name'] ?: '-' }}</div>
                                <div class="signature-role">{{ $signature['company_or_role'] ?: '-' }}</div>
                            </td>
                        @else
                            <td class="signature-cell signature-cell-empty"></td>
                        @endif
                    @endforeach
                </tr>
            </table>
        </div>
    @endif
</div>
