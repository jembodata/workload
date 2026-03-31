<div class="document-container">
    <table class="header-table">
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
                        <tr><td class="label">Page</td><td>{{ $pageLabel ?: '-' }}</td></tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="details-table">
        <tbody>
            <tr>
                <td class="left-empty-cell" rowspan="5"></td>
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
            @forelse ($previewRows as $row)
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
                    $issueStatusKey = strtolower((string) ($row['issue_status_key'] ?? 'tbd'));
                    $issueEvaluasiText = (string) ($row['issue_evaluasi'] ?? '-');
                    $hasIssueEvaluasi = trim($issueEvaluasiText) !== '' && trim($issueEvaluasiText) !== '-';
                    $issueEvaluasiItems = collect($row['issue_evaluasi_items'] ?? [])->filter(fn($item) => is_array($item));
                    $issueEvaluasiClass = match ($issueStatusKey) {
                        'closed' => 'eval-closed',
                        'progress' => 'eval-progress',
                        'opened', 'open' => 'eval-open',
                        'overdue' => 'eval-overdue',
                        'postponed' => 'eval-postponed',
                        default => 'eval-tbd',
                    };
                @endphp
                <tr class="data-row">
                    <td class="col-no center">{{ $row['no'] }}</td>
                    <td class="col-item center">{{ $row['item'] }}</td>
                    <td class="col-pembahasan">{!! nl2br(e($row['input'])) !!}</td>
                    <td class="col-rencana">{!! nl2br(e($row['output'])) !!}</td>
                    <td class="col-target center">{{ $row['target'] }}</td>
                    <td class="col-pic center">{{ $row['pic'] }}</td>
                    <td class="col-evaluasi">
                        <div class="eval-stack">
                            <div class="eval-pill {{ $taskEvaluasiClass }}">{{ $taskEvaluasiText }}</div>
                            @if ($hasIssueEvaluasi)
                                @if ($issueEvaluasiItems->isNotEmpty())
                                    @foreach ($issueEvaluasiItems as $issueItem)
                                        @php
                                            $itemKey = strtolower((string) ($issueItem['key'] ?? 'tbd'));
                                            $itemLabel = (string) ($issueItem['label'] ?? 'TBD');
                                            $itemSpacer = (int) ($issueItem['spacer'] ?? 12);
                                            $itemClass = match ($itemKey) {
                                                'closed' => 'eval-closed',
                                                'progress' => 'eval-progress',
                                                'opened', 'open' => 'eval-open',
                                                'overdue' => 'eval-overdue',
                                                'postponed' => 'eval-postponed',
                                                default => 'eval-tbd',
                                            };
                                        @endphp
                                        <div class="eval-spacer" style="height: {{ $itemSpacer }}px;"></div>
                                        <div class="eval-pill {{ $itemClass }}">{{ $itemLabel }}</div>
                                    @endforeach
                                @else
                                    <div class="eval-pill {{ $issueEvaluasiClass }}">{{ $issueEvaluasiText }}</div>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="data-row">
                    <td colspan="7" class="center empty-message">Belum ada task dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $signatureRows = collect($signatures ?? [])
            ->filter(fn($item) => filled($item['name'] ?? null) || filled($item['company_or_role'] ?? null))
            ->take(3)
            ->values();
    @endphp

    @if ($signatureRows->isNotEmpty())
        @php
            $signatureCells = collect($signatureRows->all());
            while ($signatureCells->count() < 3) {
                $signatureCells->prepend(null);
            }
        @endphp
        <div class="signature-section">
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
