<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Minutes of Meeting</title>
    <style>
        @page { size: A4 {{ $orientation ?? 'portrait' }}; margin: 12mm; }
        .report-doc {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #000;
        }
        .report-doc .center { text-align: center; }
        .report-doc table { border-collapse: collapse; border-spacing: 0; width: 100%; }
        .report-doc .document-container { width: 100%; }
        .report-doc .header-table { border: 0.75px solid #000; table-layout: fixed; }
        .report-doc .details-table,
        .report-doc .data-table-section {
            border-left: 0.75px solid #000;
            border-right: 0.75px solid #000;
            border-bottom: 0.75px solid #000;
            border-top: 0;
        }

        .report-doc .header-table td { border: 0.75px solid #000; padding: 0; }
        .report-doc .logo-cell {
            width: 18%;
            text-align: center;
            vertical-align: middle;
            padding: 7px 5px;
        }
        .report-doc .logo-wrap {
            width: 100%;
            height: 56px;
            margin: 0 auto;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
        }
        .report-doc .logo-wrap img {
            width: auto;
            height: auto;
            max-width: 112px;
            max-height: 56px;
            display: inline-block;
            vertical-align: middle;
        }
        .report-doc .title-cell { text-align: center; vertical-align: middle; }
        .report-doc .main-title { margin: 0; font-size: 14px; font-weight: 700; line-height: 1.1; }
        .report-doc .sub-title { margin-top: 2px; font-size: 10.5px; font-style: italic; font-weight: 700; color: #0054a6; }
        .report-doc .info-cell { width: 25%; vertical-align: top; padding: 0; }
        .report-doc .info-table td {
            border: 0.75px solid #000;
            font-size: 10px;
            padding: 2px 5px;
            line-height: 1.2;
            vertical-align: middle;
        }
        .report-doc .info-table tr:first-child td { border-top: 0; }
        .report-doc .info-table tr:last-child td { border-bottom: 0; }
        .report-doc .info-table td:first-child { border-left: 0; }
        .report-doc .info-table td:last-child { border-right: 0; }
        .report-doc .info-table .label { width: 45%; white-space: nowrap; font-weight: 700; }

        .report-doc .details-table { table-layout: fixed; }
        .report-doc .details-table td { border: 0.75px solid #000; padding: 2px 5px; }
        .report-doc .details-table tr:first-child td { border-top: 0; }
        .report-doc .detail-label { width: 18%; white-space: nowrap; font-weight: 700; }
        .report-doc .detail-value { width: 82%; }

        .report-doc .data-table-section { table-layout: fixed; }
        .report-doc .data-table-section th,
        .report-doc .data-table-section td {
            border: 0.75px solid #000;
            padding: 4px;
            vertical-align: top;
        }
        .report-doc .data-table-section thead tr:first-child th { border-top: 0; }
        .report-doc .data-table-section th { text-align: center; font-size: 10px; font-weight: 700; }
        .report-doc .col-no { width: 4%; }
        .report-doc .col-item { width: 14%; }
        .report-doc .col-pembahasan { width: 16%; }
        .report-doc .col-rencana { width: 36%; }
        .report-doc .col-target { width: 10%; }
        .report-doc .col-pic { width: 10%; }
        .report-doc .col-evaluasi { width: 10%; }
        .report-doc .eval-stack {
            display: block;
        }
        .report-doc .eval-pill {
            display: block;
            border: 0.75px solid #000;
            border-radius: 2px;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.25;
            padding: 2px 3px;
        }
        .report-doc .eval-pill + .eval-pill { margin-top: 3px; }
        .report-doc .eval-spacer { height: 16px; }
        .report-doc .eval-tbd { background: #e5e7eb; color: #111827; }
        .report-doc .eval-progress { background: #fde047; color: #111827; }
        .report-doc .eval-open { background: #93c5fd; color: #111827; }
        .report-doc .eval-overdue { background: #fca5a5; color: #111827; }
        .report-doc .eval-postponed { background: #d1d5db; color: #111827; }
        .report-doc .eval-closed { background: #86efac; color: #111827; }
        .report-doc .empty-message { color: #6b7280; padding: 9px 5px; }
        .report-doc .signature-section { width: 52%; padding: 0; margin-left: auto; margin-right: 0; }
        .report-doc .signature-section.signature-section--flow {
            display: block;
            margin: 0 0 0 auto;
            page-break-inside: avoid;
        }
        .report-doc .signature-section.signature-section--page-break {
            page-break-before: always;
            margin: 0 0 0 auto;
        }
        .report-doc .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 0 !important;
        }
        .report-doc .signature-cell {
            width: 33.333%;
            text-align: center;
            vertical-align: top;
            padding: 0 8px;
            border: 0 !important;
        }
        .report-doc .signature-cell-empty { padding: 0; }
        .report-doc .signature-label { font-size: 10px; }
        .report-doc .signature-space { height: 34px; }
        .report-doc .signature-line { border-top: 0.75px solid #000; margin: 0 8px 2px; }
        .report-doc .signature-name { font-size: 10px; font-weight: 700; }
        .report-doc .signature-role { font-size: 9.5px; color: #222; }
    </style>
</head>
<body>
    <div class="report-doc">
        @include('filament.pages.partials.task-report-document')
    </div>
</body>
</html>
