<div class="document-container">
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
                        <tr><td class="label">Page</td><td>{{ $pageLabel ?: '-' }}</td></tr>
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

    @php
        $columnWidths = $columnWidths ?? [
            'no' => 4,
            'project_task' => 14,
            'percent' => 16,
            'issue' => 36,
            'action_plan' => 10,
            'pic' => 10,
            'evaluasi' => 10,
        ];

        $widthNo = number_format((float) ($columnWidths['no'] ?? 4), 2, '.', '');
        $widthProjectTask = number_format((float) ($columnWidths['project_task'] ?? 14), 2, '.', '');
        $widthPercent = number_format((float) ($columnWidths['percent'] ?? 16), 2, '.', '');
        $widthIssue = number_format((float) ($columnWidths['issue'] ?? 36), 2, '.', '');
        $widthActionPlan = number_format((float) ($columnWidths['action_plan'] ?? 10), 2, '.', '');
        $widthPic = number_format((float) ($columnWidths['pic'] ?? 10), 2, '.', '');
        $widthEvaluasi = number_format((float) ($columnWidths['evaluasi'] ?? 10), 2, '.', '');
    @endphp

    @php
        $isInteractivePreview = (bool) ($isInteractivePreview ?? false);
    @endphp

    <table class="data-table-section" data-resizable-report-table="{{ $isInteractivePreview ? '1' : '0' }}">
        <colgroup>
            <col data-col-key="no" style="width: {{ $widthNo }}%;">
            <col data-col-key="project_task" style="width: {{ $widthProjectTask }}%;">
            <col data-col-key="percent" style="width: {{ $widthPercent }}%;">
            <col data-col-key="issue" style="width: {{ $widthIssue }}%;">
            <col data-col-key="action_plan" style="width: {{ $widthActionPlan }}%;">
            <col data-col-key="pic" style="width: {{ $widthPic }}%;">
            <col data-col-key="evaluasi" style="width: {{ $widthEvaluasi }}%;">
        </colgroup>
        <thead>
            <tr>
                <th class="col-no" data-col-key="no" style="width: {{ $widthNo }}%;">
                    No
                    @if ($isInteractivePreview)
                        <span class="preview-col-resize-handle" data-resize-left="no" data-resize-right="project_task" role="separator" aria-label="Resize kolom No"></span>
                    @endif
                </th>
                <th class="col-item" data-col-key="project_task" style="width: {{ $widthProjectTask }}%;">
                    Project &amp; Task
                    @if ($isInteractivePreview)
                        <span class="preview-col-resize-handle" data-resize-left="project_task" data-resize-right="percent" role="separator" aria-label="Resize kolom Project & Task"></span>
                    @endif
                </th>
                <th class="col-pembahasan" data-col-key="percent" style="width: {{ $widthPercent }}%;">
                    %
                    @if ($isInteractivePreview)
                        <span class="preview-col-resize-handle" data-resize-left="percent" data-resize-right="issue" role="separator" aria-label="Resize kolom %"></span>
                    @endif
                </th>
                <th class="col-rencana" data-col-key="issue" style="width: {{ $widthIssue }}%;">
                    Issue
                    @if ($isInteractivePreview)
                        <span class="preview-col-resize-handle" data-resize-left="issue" data-resize-right="action_plan" role="separator" aria-label="Resize kolom Issue"></span>
                    @endif
                </th>
                <th class="col-target" data-col-key="action_plan" style="width: {{ $widthActionPlan }}%;">
                    Action Plan
                    @if ($isInteractivePreview)
                        <span class="preview-col-resize-handle" data-resize-left="action_plan" data-resize-right="pic" role="separator" aria-label="Resize kolom Action Plan"></span>
                    @endif
                </th>
                <th class="col-pic" data-col-key="pic" style="width: {{ $widthPic }}%;">
                    PIC
                    @if ($isInteractivePreview)
                        <span class="preview-col-resize-handle" data-resize-left="pic" data-resize-right="evaluasi" role="separator" aria-label="Resize kolom PIC"></span>
                    @endif
                </th>
                <th class="col-evaluasi" data-col-key="evaluasi" style="width: {{ $widthEvaluasi }}%;">Evaluasi<br>Efektivitas</th>
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
                    $issueEntries = collect($row['issue_entries'] ?? [])->filter(fn($item) => is_array($item))->values();
                    $hasRealIssues = $issueEntries->isNotEmpty();
                    if ($issueEntries->isEmpty()) {
                        $issueEntries = collect([[
                            'issue_id' => 0,
                            'issue' => '-',
                            'action_plan' => '-',
                            'status_key' => '',
                            'status_label' => '',
                            'pic' => '-',
                            'has_action_plan' => false,
                        ]]);
                    }
                    $issueGroups = $issueEntries
                        ->groupBy(fn(array $item) => ((int) ($item['issue_id'] ?? 0)) > 0
                            ? 'id:' . (int) ($item['issue_id'] ?? 0)
                            : 'text:' . (string) ($item['issue'] ?? '-'))
                        ->values();
                    $rowSpan = max(1, (int) $issueGroups->sum(fn($group) => max(1, collect($group)->count())));
                    $taskRenderIndex = 0;
                @endphp
                @foreach ($issueGroups as $issueGroup)
                    @php
                        $issueGroupRows = collect($issueGroup)->values();
                        $issueGroupSpan = max(1, $issueGroupRows->count());
                    @endphp
                    @foreach ($issueGroupRows as $entryIndex => $issueEntry)
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
                            $projectName = trim((string) ($row['project_name'] ?? '-'));
                            $taskName = trim((string) ($row['task_name'] ?? '-'));
                        @endphp
                        <tr class="data-row">
                            @if ($taskRenderIndex === 0)
                                <td class="col-no center" style="width: {{ $widthNo }}%;" rowspan="{{ $rowSpan }}">{{ $row['no'] }}</td>
                                <td class="col-item" style="width: {{ $widthProjectTask }}%;" rowspan="{{ $rowSpan }}">
                                    @if ($projectName !== '' && $projectName !== '-')
                                        <div><strong>{{ $projectName }}</strong></div>
                                    @endif
                                    <div>{{ $taskName !== '' ? $taskName : '-' }}</div>
                                </td>
                                <td class="col-pembahasan center" style="width: {{ $widthPercent }}%;" rowspan="{{ $rowSpan }}">{{ (string) ($row['percent'] ?? '-') }}</td>
                            @endif

                            @if ($entryIndex === 0)
                                <td class="col-rencana" style="width: {{ $widthIssue }}%;" rowspan="{{ $issueGroupSpan }}">{!! nl2br(e((string) ($issueEntry['issue'] ?? '-'))) !!}</td>
                            @endif
                            <td class="col-target" style="width: {{ $widthActionPlan }}%;">{!! nl2br(e((string) ($issueEntry['action_plan'] ?? '-'))) !!}</td>
                            <td class="col-pic center" style="width: {{ $widthPic }}%;">{{ (string) ($issueEntry['pic'] ?? '-') }}</td>
                            <td class="col-evaluasi" style="width: {{ $widthEvaluasi }}%;">
                                <div class="eval-stack">
                                    @if (!$hasRealIssues && $taskRenderIndex === 0)
                                        <div class="eval-pill {{ $taskEvaluasiClass }}">{{ $taskEvaluasiText }}</div>
                                    @endif
                                    @if ($hasRealIssues && filled(trim($issueEntryLabel)) && trim($issueEntryLabel) !== '-')
                                        <div class="eval-pill {{ $issueEntryClass }}">{{ $issueEntryLabel }}</div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @php
                            $taskRenderIndex++;
                        @endphp
                    @endforeach
                @endforeach
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
            $signaturePushPx = (int) ($signaturePushPx ?? 0);
            $signaturePageBreakBefore = (bool) ($signaturePageBreakBefore ?? false);
        @endphp
        <div
            class="signature-section signature-section--flow {{ $signaturePageBreakBefore ? 'signature-section--page-break' : '' }}"
            style="padding-top: {{ $signaturePushPx }}px;"
        >
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
