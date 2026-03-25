<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Minutes of Meeting</title>
    <style>
        @page { size: A4 {{ $orientation ?? 'portrait' }}; margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #374151; padding: 4px; vertical-align: top; }
        .center { text-align: center; }
        .left { text-align: left; }
        .middle { vertical-align: middle; }
        .title-id { font-size: 18px; font-weight: 700; line-height: 1.1; }
        .title-en { font-size: 13px; font-style: italic; font-weight: 700; color: #0369a1; margin-top: 4px; }
        .meta { font-size: 10px; }
        .meta-label { width: 62%; white-space: nowrap; font-weight: 700; }
        .header-row { background: #f8fafc; }
        .eval-tbd { background: #fcd34d; font-weight: 700; text-align: center; }
        .eval-open { background: #ef4444; font-weight: 700; text-align: center; }
        .eval-closed { background: #86efac; font-weight: 700; text-align: center; }
        .logo-wrap { width: 110px; height: 54px; overflow: hidden; margin: 0 auto; }
        .logo-wrap img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
    </style>
</head>
<body>
    <table style="font-size: 10px;">
        <tbody>
            <tr>
                <td style="width: 120px;" class="center middle">
                    <div class="logo-wrap">
                        <img src="{{ $logoSrc }}" alt="Logo">
                    </div>
                </td>
                <td class="center middle">
                    <div class="title-id">{{ $titleId ?: '-' }}</div>
                    <div class="title-en">{{ $titleEn ?: '-' }}</div>
                </td>
                <td style="width: 205px; padding: 0;">
                    <table class="meta">
                        <tr><td class="meta-label">No. Document</td><td>{{ $documentNo ?: '-' }}</td></tr>
                        <tr><td class="meta-label">Effective date</td><td>{{ $effectiveDate ?: '-' }}</td></tr>
                        <tr><td class="meta-label">Revision</td><td>{{ $revision ?: '-' }}</td></tr>
                        <tr><td class="meta-label">Page</td><td style="white-space: nowrap;">{{ $pageLabel ?: '-' }}</td></tr>
                    </table>
                </td>
            </tr>
            <tr><td class="left"><strong>Hadir</strong></td><td colspan="2">: {{ $meetingPresent ?: '-' }}</td></tr>
            <tr><td class="left"><strong>Absen</strong></td><td colspan="2">: {{ $meetingAbsent ?: '-' }}</td></tr>
            <tr><td class="left"><strong>Hari</strong></td><td colspan="2">: {{ $meetingDay ?: '-' }}</td></tr>
            <tr><td class="left"><strong>Waktu</strong></td><td colspan="2">: {{ $meetingTime ?: '-' }}</td></tr>
            <tr><td class="left"><strong>Tempat</strong></td><td colspan="2">: {{ $meetingPlace ?: '-' }}</td></tr>
        </tbody>
    </table>

    <table style="margin-top: -1px;">
        <thead>
            <tr class="header-row">
                <th style="width:4%;" class="center">No</th>
                <th style="width:12%;" class="center">Item</th>
                <th style="width:15%;" class="center">Pembahasan (Input)</th>
                <th style="width:39%;" class="center">Rencana Tindakan (Output)</th>
                <th style="width:10%;" class="center">Target</th>
                <th style="width:9%;" class="center">PIC</th>
                <th style="width:11%;" class="center">Evaluasi Efektivitas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($previewRows as $row)
                @php
                    $evaluasiRaw = strtolower((string) ($row['evaluasi'] ?? ''));
                    $evaluasiClass = match ($evaluasiRaw) {
                        'closed' => 'eval-closed',
                        'progress', 'opened', 'open' => 'eval-open',
                        default => 'eval-tbd',
                    };
                    $evaluasiText = $evaluasiRaw === 'closed' ? 'Closed' : ($evaluasiRaw === 'progress' || $evaluasiRaw === 'opened' || $evaluasiRaw === 'open' ? 'Open' : 'TBD');
                @endphp
                <tr>
                    <td class="center">{{ $row['no'] }}</td>
                    <td class="center">{{ $row['item'] }}</td>
                    <td>{{ $row['input'] }}</td>
                    <td>{!! nl2br(e($row['output'])) !!}</td>
                    <td class="center">{{ $row['target'] }}</td>
                    <td class="center">{{ $row['pic'] }}</td>
                    <td class="{{ $evaluasiClass }}">{{ $evaluasiText }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="center" style="color: #6b7280;">Belum ada task dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
