<?php

namespace App\Filament\Pages;

use App\Models\Issue;
use App\Models\Project;
use App\Models\ReportHistory;
use App\Models\Staff;
use App\Models\Task;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class TaskReportBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Create Report';
    protected static ?string $slug = 'task-report-builder';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.task-report-builder';

    public ?array $formData = [];

    // Task picker state
    public string $taskSearch = '';
    public ?int $taskFilterStaffId = null;
    public ?int $taskFilterProjectId = null;
    public string $taskFilterStatus = '';
    public bool $showOnlySelectedTasks = false;
    public int $taskPickerPage = 1;
    public int $taskPickerPerPage = 25;

    // Issue picker state
    public string $issueSearch = '';
    public string $issueFilterStatus = '';
    public string $issueFilterPriority = '';
    public ?int $issueFilterStaffId = null;
    public bool $showOnlySelectedIssues = false;
    public int $issuePickerPage = 1;
    public int $issuePickerPerPage = 20;

    /** @var array<string> */
    public array $selectedTaskIds = [];

    /** @var array<string> */
    public array $selectedIssueIds = [];

    public function mount(): void
    {
        $this->form->fill([
            'title_id' => 'isi JUDUL (ID)',
            'title_en' => 'isi TITLE (EN)',
            'meeting_present' => [],
            'meeting_absent' => [],
            'meeting_day' => now()->toDateString(),
            'meeting_time' => '08.00 - 10.00',
            'meeting_place' => '',
            'document_no' => '',
            'effective_date' => now()->toDateString(),
            'revision' => '0',
            'signatures' => [],
        ]);
    }

    public function renderPdfAction(): Action
    {
        return Action::make('renderPdf')
            ->label('Render PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('primary')
            ->form([
                Forms\Components\Select::make('orientation')
                    ->label('Orientation')
                    ->options([
                        'portrait' => 'Portrait',
                        'landscape' => 'Landscape',
                    ])
                    ->default('portrait')
                    ->native(false)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->renderPdf((string) ($data['orientation'] ?? 'portrait'));
            });
    }

    public function renderDocxAction(): Action
    {
        return Action::make('renderDocx')
            ->label('Render DOCX')
            ->icon('heroicon-o-document-text')
            ->color('gray')
            ->form([
                Forms\Components\Select::make('orientation')
                    ->label('Orientation')
                    ->options([
                        'portrait' => 'Portrait',
                        'landscape' => 'Landscape',
                    ])
                    ->default('portrait')
                    ->native(false)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->renderDocx((string) ($data['orientation'] ?? 'portrait'));
            });
    }

    public function renderPdf(string $orientation = 'portrait'): void
    {
        $orientation = in_array($orientation, ['portrait', 'landscape'], true) ? $orientation : 'portrait';

        $data = array_merge($this->getReportData(), [
            'logoSrc' => public_path('images/logo_report.png'),
            'orientation' => $orientation,
        ]);

        $pdfOutput = $this->buildPdfOutput($data, $orientation, $totalPages);
        $data['pageLabel'] = "1 dari {$totalPages}";

        $now = now();
        $fileName = 'minutes-meeting-' . $now->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.pdf';
        $pdfPath = 'reports/history/' . $now->format('Y/m') . '/' . $fileName;
        Storage::disk('local')->put($pdfPath, $pdfOutput);

        $history = ReportHistory::query()->create([
            'title_id' => (string) ($data['titleId'] ?? ''),
            'title_en' => (string) ($data['titleEn'] ?? ''),
            'document_no' => (string) ($data['documentNo'] ?? ''),
            'revision' => (string) ($data['revision'] ?? ''),
            'orientation' => $orientation,
            'page_label' => (string) ($data['pageLabel'] ?? ''),
            'pdf_path' => $pdfPath,
            'docx_path' => null,
            'printed_by' => auth()->id(),
            'printed_at' => $now,
            'payload' => [
                'form_data' => $this->formData,
                'selected_task_ids' => $this->selectedTaskIds,
                'selected_issue_ids' => $this->selectedIssueIds,
                'report_data' => $data,
            ],
        ]);

        $url = route('task-report.history.pdf', ['history' => $history]);
        $this->js("window.open('{$url}', '_blank')");
    }

    public function renderDocx(string $orientation = 'portrait'): void
    {
        if (!class_exists(PhpWord::class)) {
            Notification::make()
                ->title('DOCX dependency belum terpasang')
                ->body('Jalankan: composer require phpoffice/phpword:^1.2')
                ->danger()
                ->send();
            return;
        }

        $orientation = in_array($orientation, ['portrait', 'landscape'], true) ? $orientation : 'portrait';
        $data = array_merge($this->getReportData(), [
            'logoSrc' => public_path('images/logo_report.png'),
            'orientation' => $orientation,
        ]);

        $docxOutput = $this->buildDocxOutput($data, $orientation);

        $now = now();
        $fileName = 'minutes-meeting-' . $now->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.docx';
        $docxPath = 'reports/history/' . $now->format('Y/m') . '/' . $fileName;
        Storage::disk('local')->put($docxPath, $docxOutput);

        $history = ReportHistory::query()->create([
            'title_id' => (string) ($data['titleId'] ?? ''),
            'title_en' => (string) ($data['titleEn'] ?? ''),
            'document_no' => (string) ($data['documentNo'] ?? ''),
            'revision' => (string) ($data['revision'] ?? ''),
            'orientation' => $orientation,
            'page_label' => (string) ($data['pageLabel'] ?? ''),
            'pdf_path' => null,
            'docx_path' => $docxPath,
            'printed_by' => auth()->id(),
            'printed_at' => $now,
            'payload' => [
                'form_data' => $this->formData,
                'selected_task_ids' => $this->selectedTaskIds,
                'selected_issue_ids' => $this->selectedIssueIds,
                'report_data' => $data,
            ],
        ]);

        $url = route('task-report.history.docx', ['history' => $history]);
        $this->js("window.open('{$url}', '_blank')");
    }

    protected function buildPdfOutput(array $data, string $orientation, ?int &$totalPages = null): string
    {
        $probe = Pdf::loadView('filament.pages.task-report-builder-pdf', $data)
            ->setPaper('a4', $orientation);

        $dompdf = $probe->getDomPDF();
        $dompdf->render();
        $totalPages = max(1, (int) $dompdf->getCanvas()->get_page_count());

        $data['pageLabel'] = "1 dari {$totalPages}";

        return Pdf::loadView('filament.pages.task-report-builder-pdf', $data)
            ->setPaper('a4', $orientation)
            ->output();
    }

    protected function buildDocxOutput(array $data, string $orientation): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $isLandscape = $orientation === 'landscape';
        $totalWidth = $isLandscape ? 14700 : 9800;
        $wLogo = (int) round($totalWidth * 0.1735);
        $wMeta = (int) round($totalWidth * 0.2245);
        $wMetaLabel = (int) floor($wMeta * 0.58);
        $wMetaValue = $wMeta - $wMetaLabel;
        $wTitle = $totalWidth - $wLogo - $wMeta;

        $section = $phpWord->addSection([
            'orientation' => $isLandscape ? 'landscape' : 'portrait',
            'marginTop' => 680,
            'marginBottom' => 680,
            'marginLeft' => 680,
            'marginRight' => 680,
        ]);

        $headerTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => $totalWidth,
            'unit' => 'dxa',
        ]);

        $metaRows = [
            ['No. Document', (string) ($data['documentNo'] ?? '-')],
            ['Effective date', (string) ($data['effectiveDate'] ?? '-')],
            ['Revision', (string) ($data['revision'] ?? '-')],
            ['Page', (string) ($data['pageLabel'] ?? '-')],
        ];

        foreach ($metaRows as $index => [$label, $value]) {
            $headerTable->addRow(290);

            if ($index === 0) {
                $logoCell = $headerTable->addCell($wLogo, ['valign' => 'center', 'vMerge' => 'restart']);
                $logoPath = (string) ($data['logoSrc'] ?? '');
                if ($logoPath !== '' && is_file($logoPath)) {
                    $logoCell->addImage($logoPath, ['width' => 72, 'height' => 36, 'alignment' => 'center']);
                } else {
                    $logoCell->addText('LOGO', ['bold' => true], ['alignment' => 'center']);
                }

                $titleCell = $headerTable->addCell($wTitle, ['valign' => 'center', 'vMerge' => 'restart']);
                $titleCell->addText((string) ($data['titleId'] ?? '-'), ['bold' => true, 'size' => 12], ['alignment' => 'center', 'spaceAfter' => 30]);
                $titleCell->addText((string) ($data['titleEn'] ?? '-'), ['bold' => true, 'italic' => true, 'size' => 10, 'color' => '0054A6'], ['alignment' => 'center']);
            } else {
                $headerTable->addCell($wLogo, ['vMerge' => 'continue']);
                $headerTable->addCell($wTitle, ['vMerge' => 'continue']);
            }

            $headerTable->addCell($wMetaLabel, ['valign' => 'center'])->addText($label, ['bold' => true, 'size' => 9]);
            $headerTable->addCell($wMetaValue, ['valign' => 'center'])->addText($value, ['size' => 9]);
        }

        $detailsTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 40,
            'width' => $totalWidth,
            'unit' => 'dxa',
        ]);

        $detailRows = [
            ['Hadir', (string) ($data['meetingPresent'] ?? '-')],
            ['Absen', (string) ($data['meetingAbsent'] ?? '-')],
            ['Hari', (string) ($data['meetingDay'] ?? '-')],
            ['Waktu', (string) ($data['meetingTime'] ?? '-')],
            ['Tempat', (string) ($data['meetingPlace'] ?? '-')],
        ];

        foreach ($detailRows as [$label, $value]) {
            $detailsTable->addRow();
            $detailsTable->addCell($wLogo, ['valign' => 'center'])->addText('');
            $detailLabelWidth = (int) round($totalWidth * 0.1428);
            $detailsTable->addCell($detailLabelWidth, ['valign' => 'center'])->addText($label, ['bold' => true]);
            $detailsTable->addCell($totalWidth - $wLogo - $detailLabelWidth, ['valign' => 'center'])->addText(': ' . $value);
        }

        $dataTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 40,
            'width' => $totalWidth,
            'unit' => 'dxa',
        ]);

        $wNo = (int) round($totalWidth * 0.04);
        $wItem = (int) round($totalWidth * 0.10);
        $wPembahasan = (int) round($totalWidth * 0.20);
        $wRencana = (int) round($totalWidth * 0.36);
        $wTarget = (int) round($totalWidth * 0.10);
        $wPic = (int) round($totalWidth * 0.10);
        $wEvaluasi = $totalWidth - $wNo - $wItem - $wPembahasan - $wRencana - $wTarget - $wPic;

        $dataTable->addRow();
        $dataTable->addCell($wNo)->addText('No', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wItem)->addText('Item', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wPembahasan)->addText("Pembahasan\n(Input)", ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wRencana)->addText('Rencana Tindakan (Output)', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wTarget)->addText('Target', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wPic)->addText('PIC', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wEvaluasi)->addText("Evaluasi\nEfektivitas", ['bold' => true], ['alignment' => 'center']);

        $rows = Arr::wrap($data['previewRows'] ?? []);
        if (empty($rows)) {
            $dataTable->addRow();
            $dataTable->addCell(9780, ['gridSpan' => 7])->addText('Belum ada task dipilih.', ['color' => '6B7280'], ['alignment' => 'center']);
        } else {
            foreach ($rows as $row) {
                $taskStatusKey = $this->normalizeReportStatus((string) ($row['task_status_key'] ?? $row['evaluasi'] ?? ''));
                $issueStatusKey = $this->normalizeReportStatus((string) ($row['issue_status_key'] ?? 'tbd'));
                $taskEvaluasiText = (string) ($row['task_evaluasi'] ?? $this->reportStatusLabel($taskStatusKey));
                $issueEvaluasiText = (string) ($row['issue_evaluasi'] ?? '-');
                $issueEvaluasiItems = collect($row['issue_evaluasi_items'] ?? [])
                    ->filter(fn($item) => is_array($item))
                    ->values();
                $taskTextColor = match ($taskStatusKey) {
                    'closed' => '166534',
                    'progress' => 'A16207',
                    'opened' => '1D4ED8',
                    'overdue' => 'B91C1C',
                    'postponed' => '4B5563',
                    default => '111827',
                };
                $issueTextColor = match ($issueStatusKey) {
                    'closed' => '166534',
                    'progress' => 'A16207',
                    'opened' => '1D4ED8',
                    'overdue' => 'B91C1C',
                    'postponed' => '4B5563',
                    default => '111827',
                };

                $dataTable->addRow();
                $dataTable->addCell($wNo)->addText((string) ($row['no'] ?? ''), [], ['alignment' => 'center']);
                $dataTable->addCell($wItem)->addText($this->docxPlain((string) ($row['item'] ?? '-')), [], ['alignment' => 'center']);
                $this->addDocxMultilineCell($dataTable->addCell($wPembahasan), (string) ($row['input'] ?? '-'));
                $this->addDocxMultilineCell($dataTable->addCell($wRencana), (string) ($row['output'] ?? '-'));
                $dataTable->addCell($wTarget)->addText($this->docxPlain((string) ($row['target'] ?? '-')), [], ['alignment' => 'center']);
                $dataTable->addCell($wPic)->addText($this->docxPlain((string) ($row['pic'] ?? '-')), [], ['alignment' => 'center']);
                $evalCell = $dataTable->addCell($wEvaluasi);
                $evalCell->addText($taskEvaluasiText, ['bold' => true, 'color' => $taskTextColor], ['alignment' => 'center', 'spaceAfter' => 20]);
                if ($issueEvaluasiItems->isNotEmpty()) {
                    $evalCell->addTextBreak(1);
                    foreach ($issueEvaluasiItems as $issueItem) {
                        $itemKey = $this->normalizeReportStatus((string) ($issueItem['key'] ?? 'tbd'));
                        $itemLabel = (string) ($issueItem['label'] ?? 'TBD');
                        $itemColor = match ($itemKey) {
                            'closed' => '166534',
                            'progress' => 'A16207',
                            'opened' => '1D4ED8',
                            'overdue' => 'B91C1C',
                            'postponed' => '4B5563',
                            default => '111827',
                        };

                        $evalCell->addText($itemLabel, ['size' => 9, 'color' => $itemColor], ['alignment' => 'center', 'spaceAfter' => 10]);
                    }
                } elseif (trim($issueEvaluasiText) !== '' && trim($issueEvaluasiText) !== '-') {
                    $evalCell->addTextBreak(1);
                    $evalCell->addText($issueEvaluasiText, ['size' => 9, 'color' => $issueTextColor], ['alignment' => 'center']);
                }
            }
        }

        $signatures = array_slice(array_values(Arr::wrap($data['signatures'] ?? [])), 0, 3);
        if (!empty($signatures)) {
            $section->addTextBreak(1);

            $signatureTable = $section->addTable([
                'borderSize' => 0,
                'width' => $totalWidth,
                'unit' => 'dxa',
            ]);

            foreach ($signatures as $signature) {
                $signatureTable->addRow();
                $signatureTable->addCell((int) round($totalWidth * 0.48), ['borderSize' => 0])->addText('');

                $signCell = $signatureTable->addCell((int) round($totalWidth * 0.52), ['borderSize' => 0]);
                $signCell->addText('Disetujui,', ['size' => 9], ['alignment' => 'center']);
                $signCell->addTextBreak(2);
                $signCell->addText('____________________________', ['size' => 9], ['alignment' => 'center', 'spaceAfter' => 40]);
                $signCell->addText((string) ($signature['name'] ?? '-'), ['bold' => true, 'size' => 10], ['alignment' => 'center']);
                $signCell->addText((string) ($signature['company_or_role'] ?? '-'), ['size' => 9], ['alignment' => 'center']);
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'docx_report_');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        $content = (string) file_get_contents($tmpPath);
        @unlink($tmpPath);

        return $content;
    }

    protected function addDocxMultilineCell($cell, string $value): void
    {
        $lines = preg_split('/\R/u', $this->docxPlain($value)) ?: [''];
        foreach ($lines as $index => $line) {
            $cell->addText($line);
            if ($index < count($lines) - 1) {
                $cell->addTextBreak();
            }
        }
    }

    protected function docxPlain(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $stripped = strip_tags($decoded);
        return trim((string) preg_replace('/[ \t]+/', ' ', $stripped));
    }

    protected function getViewData(): array
    {
        $taskPickerData = $this->getTaskPickerData();
        $issuePickerData = $this->getIssuePickerData();

        return array_merge([
            'availableTasks' => $taskPickerData['items'],
            'taskPickerMeta' => $taskPickerData['meta'],
            'staffFilterOptions' => Staff::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            'projectFilterOptions' => Project::query()->orderBy('project_name')->pluck('project_name', 'id')->toArray(),
            'statusFilterOptions' => Task::query()->select('status')->distinct()->orderBy('status')->pluck('status')->all(),

            'availableIssues' => $issuePickerData['items'],
            'issuePickerMeta' => $issuePickerData['meta'],
            'issueStatusOptions' => Issue::query()->select('status')->distinct()->orderBy('status')->pluck('status')->all(),
            'issuePriorityOptions' => Issue::query()->select('priority')->distinct()->orderBy('priority')->pluck('priority')->all(),
            'issueStaffOptions' => Staff::query()->orderBy('name')->pluck('name', 'id')->toArray(),
        ], $this->getReportData());
    }

    // region: update hooks
    public function updatedSelectedTaskIds(): void
    {
        $this->selectedTaskIds = collect($this->selectedTaskIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values()
            ->all();

        // Keep selected issues only from selected tasks
        $selectedTaskIds = $this->getSelectedTaskIdsAsInt();
        if (empty($selectedTaskIds)) {
            $this->selectedIssueIds = [];
            $this->issuePickerPage = 1;
            return;
        }

        $allowedIssueIds = Issue::query()
            ->whereIn('task_id', $selectedTaskIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $allowedMap = array_fill_keys($allowedIssueIds, true);
        $this->selectedIssueIds = array_values(array_filter(
            $this->selectedIssueIds,
            fn($id) => isset($allowedMap[(string) $id])
        ));

        $this->issuePickerPage = 1;
    }

    public function updatedSelectedIssueIds(): void
    {
        $this->selectedIssueIds = collect($this->selectedIssueIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function updatedTaskSearch(): void { $this->taskPickerPage = 1; }
    public function updatedTaskFilterStaffId(): void { $this->taskPickerPage = 1; }
    public function updatedTaskFilterProjectId(): void { $this->taskPickerPage = 1; }
    public function updatedTaskFilterStatus(): void { $this->taskPickerPage = 1; }
    public function updatedShowOnlySelectedTasks(): void { $this->taskPickerPage = 1; }

    public function updatedIssueSearch(): void { $this->issuePickerPage = 1; }
    public function updatedIssueFilterStatus(): void { $this->issuePickerPage = 1; }
    public function updatedIssueFilterPriority(): void { $this->issuePickerPage = 1; }
    public function updatedIssueFilterStaffId(): void { $this->issuePickerPage = 1; }
    public function updatedShowOnlySelectedIssues(): void { $this->issuePickerPage = 1; }
    // endregion

    // region: task picker actions
    public function nextTaskPickerPage(): void
    {
        if ($this->taskPickerPage < $this->resolveTaskPickerLastPage()) {
            $this->taskPickerPage++;
        }
    }

    public function previousTaskPickerPage(): void
    {
        if ($this->taskPickerPage > 1) {
            $this->taskPickerPage--;
        }
    }

    public function selectCurrentPageTasks(): void
    {
        $ids = $this->getTaskPickerData()['items']->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->selectedTaskIds = array_values(array_unique([...$this->selectedTaskIds, ...$ids]));
    }

    public function selectFilteredTasks(): void
    {
        $ids = (clone $this->buildTaskPickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedTaskIds = array_values(array_unique([...$this->selectedTaskIds, ...$ids]));
    }

    public function unselectFilteredTasks(): void
    {
        $filteredIds = (clone $this->buildTaskPickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $filteredMap = array_fill_keys($filteredIds, true);

        $this->selectedTaskIds = array_values(array_filter(
            $this->selectedTaskIds,
            fn($id) => !isset($filteredMap[(string) $id])
        ));
    }

    public function clearSelectedTasks(): void
    {
        $this->selectedTaskIds = [];
        $this->selectedIssueIds = [];
    }
    // endregion

    // region: issue picker actions
    public function nextIssuePickerPage(): void
    {
        if ($this->issuePickerPage < $this->resolveIssuePickerLastPage()) {
            $this->issuePickerPage++;
        }
    }

    public function previousIssuePickerPage(): void
    {
        if ($this->issuePickerPage > 1) {
            $this->issuePickerPage--;
        }
    }

    public function selectCurrentPageIssues(): void
    {
        $ids = $this->getIssuePickerData()['items']->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->selectedIssueIds = array_values(array_unique([...$this->selectedIssueIds, ...$ids]));
    }

    public function selectFilteredIssues(): void
    {
        $ids = (clone $this->buildIssuePickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedIssueIds = array_values(array_unique([...$this->selectedIssueIds, ...$ids]));
    }

    public function unselectFilteredIssues(): void
    {
        $filteredIds = (clone $this->buildIssuePickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $filteredMap = array_fill_keys($filteredIds, true);

        $this->selectedIssueIds = array_values(array_filter(
            $this->selectedIssueIds,
            fn($id) => !isset($filteredMap[(string) $id])
        ));
    }

    public function clearSelectedIssues(): void
    {
        $this->selectedIssueIds = [];
    }
    // endregion

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Form Report')
                    ->description('Isi data meeting untuk ditampilkan di preview.')
                    ->schema([
                        Forms\Components\TextInput::make('title_id')
                            ->label('Judul (ID)')
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('title_en')
                            ->label('Judul (EN)')
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('meeting_present')
                            ->label('Hadir')
                            ->options(fn(Get $get) => $this->getStaffOptionsExcluding($get('meeting_absent')))
                            ->searchable()
                            ->preload()
                            ->multiple()
                            ->native(false)
                            ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                                $presentIds = collect(Arr::wrap($state))
                                    ->map(fn($id) => (int) $id)
                                    ->filter()
                                    ->values();

                                $filteredAbsent = collect(Arr::wrap($get('meeting_absent')))
                                    ->map(fn($id) => (int) $id)
                                    ->reject(fn($id) => $presentIds->contains($id))
                                    ->values()
                                    ->all();

                                $set('meeting_absent', $filteredAbsent);
                            })
                            ->live(),
                        Forms\Components\Select::make('meeting_absent')
                            ->label('Absen')
                            ->options(fn(Get $get) => $this->getStaffOptionsExcluding($get('meeting_present')))
                            ->searchable()
                            ->preload()
                            ->multiple()
                            ->native(false)
                            ->live(),
                        Forms\Components\DatePicker::make('meeting_day')
                            ->label('Hari')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->live(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('meeting_time')
                                    ->label('Waktu')
                                    ->live(),
                                Forms\Components\TextInput::make('meeting_place')
                                    ->label('Tempat')
                                    ->live(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('document_no')
                                    ->label('No. Document')
                                    ->live(),
                                Forms\Components\DatePicker::make('effective_date')
                                    ->label('Effective Date')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->live(),
                                Forms\Components\TextInput::make('revision')
                                    ->label('Revision')
                                    ->live(),
                            ]),
                        Forms\Components\Repeater::make('signatures')
                            ->label('Tanda Tangan (Opsional)')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama')
                                    ->maxLength(120)
                                    ->live(),
                                Forms\Components\TextInput::make('company_or_role')
                                    ->label('Nama Perusahaan / Jabatan')
                                    ->maxLength(180)
                                    ->live(),
                            ])
                            ->default([])
                            ->addActionLabel('Tambah Tanda Tangan')
                            ->maxItems(3)
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                            ->live(),
                    ]),
            ])
            ->statePath('formData');
    }

    protected function estimateTotalPages(array $rows): int
    {
        if (empty($rows)) {
            return 1;
        }

        $availableUnitsPerPage = 42;
        $usedUnits = 0;

        foreach ($rows as $row) {
            $output = (string) ($row['output'] ?? '');
            $input = (string) ($row['input'] ?? '');

            $lineUnits = max(
                1,
                (int) ceil(mb_strlen($output) / 110),
                (int) ceil(mb_strlen($input) / 70),
            );

            $usedUnits += 1 + $lineUnits;
        }

        return max(1, (int) ceil($usedUnits / $availableUnitsPerPage));
    }

    protected function getReportData(): array
    {
        $previewRows = $this->getPreviewRows();
        $totalPages = $this->estimateTotalPages($previewRows);

        return [
            'previewRows' => $previewRows,
            'titleId' => (string) data_get($this->formData, 'title_id', ''),
            'titleEn' => (string) data_get($this->formData, 'title_en', ''),
            'meetingPresent' => $this->formatParticipants(data_get($this->formData, 'meeting_present')),
            'meetingAbsent' => $this->formatParticipants(data_get($this->formData, 'meeting_absent')),
            'meetingDay' => $this->formatMeetingDay(data_get($this->formData, 'meeting_day')),
            'meetingTime' => (string) data_get($this->formData, 'meeting_time', ''),
            'meetingPlace' => (string) data_get($this->formData, 'meeting_place', ''),
            'documentNo' => (string) data_get($this->formData, 'document_no', ''),
            'effectiveDate' => $this->formatIndonesianDate(data_get($this->formData, 'effective_date')),
            'revision' => (string) data_get($this->formData, 'revision', ''),
            'signatures' => $this->formatSignatures(data_get($this->formData, 'signatures', [])),
            'pageLabel' => "1 dari {$totalPages}",
            'logoSrc' => asset('images/logo_report.png'),
        ];
    }

    protected function formatMeetingDay(mixed $value): string
    {
        return $this->formatIndonesianDate($value, withDayName: true);
    }

    protected function formatIndonesianDate(mixed $value, bool $withDayName = false): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return Carbon::parse((string) $value)
                ->locale('id')
                ->translatedFormat($withDayName ? 'l, d F Y' : 'd F Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected function formatParticipants(mixed $value): string
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (blank($value)) {
            return '';
        }

        if (!is_array($value)) {
            return trim((string) $value);
        }

        $orderedIds = collect($value)
            ->map(fn($id) => is_numeric($id) ? (int) $id : null)
            ->filter()
            ->values()
            ->all();

        if (empty($orderedIds)) {
            return collect($value)
                ->map(fn($item) => is_scalar($item) ? trim((string) $item) : '')
                ->filter()
                ->implode(', ');
        }

        $namesById = Staff::query()->whereIn('id', $orderedIds)->pluck('name', 'id');

        return collect($orderedIds)
            ->map(fn($id) => $namesById->get($id))
            ->filter()
            ->implode(', ');
    }

    /**
     * @return array<int, array{name:string,company_or_role:string}>
     */
    protected function formatSignatures(mixed $value): array
    {
        $items = collect(Arr::wrap($value))
            ->map(function ($item): array {
                $name = trim((string) data_get($item, 'name', ''));
                $companyOrRole = trim((string) data_get($item, 'company_or_role', ''));

                return [
                    'name' => $name,
                    'company_or_role' => $companyOrRole,
                ];
            })
            ->filter(fn(array $item) => $item['name'] !== '' || $item['company_or_role'] !== '')
            ->take(3)
            ->values()
            ->all();

        return $items;
    }

    protected function buildTaskPickerQuery(): Builder
    {
        return Task::query()
            ->with(['staff', 'project'])
            ->withCount('issues')
            ->when($this->showOnlySelectedTasks, fn(Builder $query) => $query->whereIn('id', $this->getSelectedTaskIdsAsInt()))
            ->when($this->taskFilterStaffId, fn(Builder $query) => $query->where('staff_id', $this->taskFilterStaffId))
            ->when($this->taskFilterProjectId, fn(Builder $query) => $query->where('project_id', $this->taskFilterProjectId))
            ->when($this->taskFilterStatus !== '', fn(Builder $query) => $query->where('status', $this->taskFilterStatus))
            ->when($this->taskSearch !== '', function (Builder $query) {
                $term = '%' . $this->taskSearch . '%';

                $query->where(function (Builder $sub) use ($term) {
                    $sub->where('task_name', 'like', $term)
                        ->orWhere('input', 'like', $term)
                        ->orWhere('output', 'like', $term)
                        ->orWhereHas('staff', fn(Builder $staff) => $staff->where('name', 'like', $term));
                });
            })
            ->latest();
    }

    protected function buildIssuePickerQuery(): Builder
    {
        $selectedTaskIds = $this->getSelectedTaskIdsAsInt();

        return Issue::query()
            ->with(['task', 'staff'])
            ->whereIn('task_id', empty($selectedTaskIds) ? [-1] : $selectedTaskIds)
            ->when($this->showOnlySelectedIssues, fn(Builder $query) => $query->whereIn('id', collect($this->selectedIssueIds)->map(fn($id) => (int) $id)->all()))
            ->when($this->issueFilterStatus !== '', fn(Builder $query) => $query->where('status', $this->issueFilterStatus))
            ->when($this->issueFilterPriority !== '', fn(Builder $query) => $query->where('priority', $this->issueFilterPriority))
            ->when($this->issueFilterStaffId, fn(Builder $query) => $query->where('staff_id', $this->issueFilterStaffId))
            ->when($this->issueSearch !== '', function (Builder $query) {
                $term = '%' . $this->issueSearch . '%';

                $query->where(function (Builder $sub) use ($term) {
                    $sub->where('issue_name', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('task', fn(Builder $task) => $task->where('task_name', 'like', $term))
                        ->orWhereHas('staff', fn(Builder $staff) => $staff->where('name', 'like', $term));
                });
            })
            ->latest();
    }

    protected function resolveTaskPickerLastPage(): int
    {
        $total = (clone $this->buildTaskPickerQuery())->count();

        return max(1, (int) ceil($total / $this->taskPickerPerPage));
    }

    protected function resolveIssuePickerLastPage(): int
    {
        $total = (clone $this->buildIssuePickerQuery())->count();

        return max(1, (int) ceil($total / $this->issuePickerPerPage));
    }

    protected function getTaskPickerData(): array
    {
        $query = $this->buildTaskPickerQuery();
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $this->taskPickerPerPage));

        $page = max(1, min($this->taskPickerPage, $lastPage));
        $this->taskPickerPage = $page;

        $items = (clone $query)
            ->forPage($page, $this->taskPickerPerPage)
            ->get();

        $from = $total === 0 ? 0 : (($page - 1) * $this->taskPickerPerPage) + 1;
        $to = min($total, $page * $this->taskPickerPerPage);

        return [
            'items' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    protected function getIssuePickerData(): array
    {
        $query = $this->buildIssuePickerQuery();
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $this->issuePickerPerPage));

        $page = max(1, min($this->issuePickerPage, $lastPage));
        $this->issuePickerPage = $page;

        $items = (clone $query)
            ->forPage($page, $this->issuePickerPerPage)
            ->get();

        $from = $total === 0 ? 0 : (($page - 1) * $this->issuePickerPerPage) + 1;
        $to = min($total, $page * $this->issuePickerPerPage);

        return [
            'items' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    protected function getPreviewRows(): array
    {
        $taskIds = $this->getSelectedTaskIdsAsInt();
        if (empty($taskIds)) {
            return [];
        }

        $selectedIssueIds = collect($this->selectedIssueIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $issuesByTask = Issue::query()
            ->whereIn('id', empty($selectedIssueIds) ? [-1] : $selectedIssueIds)
            ->get(['id', 'task_id', 'issue_name', 'description', 'status'])
            ->groupBy('task_id');

        $tasks = Task::query()
            ->with(['staff', 'project'])
            ->whereIn('id', $taskIds)
            ->get()
            ->keyBy('id');

        return collect($taskIds)
            ->map(fn(int $id) => $tasks->get($id))
            ->filter()
            ->values()
            ->map(function (Task $task, int $index) use ($issuesByTask) {
                $output = (string) ($task->output ?? '-');
                $selectedIssues = $issuesByTask->get($task->id, collect());
                $taskStatus = $this->normalizeReportStatus((string) ($task->status ?? ''));

                if ($selectedIssues->isNotEmpty()) {
                    $issueText = $selectedIssues->map(function (Issue $issue): string {
                        $name = trim((string) $issue->issue_name);
                        $status = trim((string) ($issue->status ?? ''));
                        $desc = html_entity_decode((string) ($issue->description ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $desc = str_replace("\u{00A0}", ' ', $desc);
                        $desc = trim(strip_tags($desc));
                        $desc = preg_replace('/\s+/', ' ', $desc) ?? $desc;

                        $line = "- {$name}";
                        if ($status !== '') {
                            $line .= " ({$status})";
                        }
                        if ($desc !== '') {
                            $line .= ': ' . $desc;
                        }

                        return $line;
                    })->implode("\n");

                    $output = trim($output . "\n\n" . $issueText);
                }

                $taskEvaluasi = $this->reportStatusLabel($taskStatus);
                $issueStatusMeta = $this->summarizeIssueStatuses($selectedIssues);

                return [
                    'no' => $index + 1,
                    'item' => (string) ($task->task_name ?? '-'),
                    'input' => (string) ($task->input ?? '-'),
                    'output' => $output,
                    'target' => $task->tanggal ? $this->formatIndonesianDate($task->tanggal) : '-',
                    'pic' => (string) ($task->staff?->name ?? '-'),
                    'task_status_key' => $taskStatus,
                    'task_evaluasi' => $taskEvaluasi,
                    'issue_status_key' => $issueStatusMeta['primary_key'],
                    'issue_evaluasi' => $issueStatusMeta['label'],
                    'issue_evaluasi_items' => $issueStatusMeta['items'],
                    // Backward compatibility for existing templates/logic.
                    'evaluasi' => $taskEvaluasi,
                ];
            })
            ->all();
    }

    /**
     * @return array{
     *   label:string,
     *   primary_key:string,
     *   items:array<int, array{key:string,label:string}>
     * }
     */
    protected function summarizeIssueStatuses(Collection $selectedIssues): array
    {
        if ($selectedIssues->isEmpty()) {
            return ['label' => '-', 'primary_key' => 'tbd', 'items' => []];
        }

        $statusItems = $selectedIssues
            ->map(function (Issue $issue): array {
                $key = $this->normalizeReportStatus((string) ($issue->status ?? ''));
                return [
                    'key' => $key,
                    'label' => $this->reportStatusLabel($key),
                ];
            })
            ->filter(fn(array $item) => ($item['key'] ?? '') !== '')
            ->values();

        if ($statusItems->isEmpty()) {
            return ['label' => '-', 'primary_key' => 'tbd', 'items' => []];
        }

        $normalizedStatuses = $statusItems
            ->pluck('key')
            ->filter()
            ->unique()
            ->values();

        $labels = $statusItems
            ->pluck('label')
            ->values();

        return [
            'label' => $labels->implode(', '),
            'primary_key' => $this->pickPrimaryStatusKey($normalizedStatuses),
            'items' => $statusItems->all(),
        ];
    }

    protected function normalizeReportStatus(string $status): string
    {
        $normalized = Str::of($status)
            ->lower()
            ->replace('-', '_')
            ->replace(' ', '_')
            ->trim()
            ->toString();

        return match ($normalized) {
            'opened', 'open', 'todo', 'to_do', 'backlog', 'duplicate' => 'opened',
            'progress', 'in_progress' => 'progress',
            'closed', 'close', 'done' => 'closed',
            'overdue' => 'overdue',
            'postponed', 'postpone', 'canceled', 'cancelled' => 'postponed',
            default => $normalized,
        };
    }

    protected function reportStatusLabel(string $normalizedStatus): string
    {
        return match ($normalizedStatus) {
            'opened' => 'Open',
            'progress' => 'Progress',
            'closed' => 'Closed',
            'overdue' => 'Overdue',
            'postponed' => 'Postponed',
            default => 'TBD',
        };
    }

    protected function pickPrimaryStatusKey(Collection $statuses): string
    {
        foreach (['overdue', 'progress', 'opened', 'closed', 'postponed'] as $priorityStatus) {
            if ($statuses->contains($priorityStatus)) {
                return $priorityStatus;
            }
        }

        return (string) ($statuses->first() ?? 'tbd');
    }

    /** @return array<int> */
    protected function getSelectedTaskIdsAsInt(): array
    {
        return collect($this->selectedTaskIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function getStaffOptionsExcluding(mixed $excludedIds): array
    {
        $exclude = collect(Arr::wrap($excludedIds))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Staff::query()
            ->when(!empty($exclude), fn(Builder $query) => $query->whereNotIn('id', $exclude))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
