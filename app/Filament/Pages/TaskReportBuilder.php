<?php

namespace App\Filament\Pages;

use App\Models\Issue;
use App\Models\IssueActionPlan;
use App\Models\Project;
use App\Models\ReportHistory;
use App\Models\Staff;
use App\Models\Task;
use App\Models\TaskReportBuilderPreference;
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

    // Action plan picker state
    public string $actionPlanSearch = '';
    public string $actionPlanFilterStatus = '';
    public ?int $actionPlanFilterStaffId = null;
    public bool $showOnlySelectedActionPlans = false;
    public int $actionPlanPickerPage = 1;
    public int $actionPlanPickerPerPage = 20;

    /** @var array<string> */
    public array $selectedTaskIds = [];

    /** @var array<string> */
    public array $selectedIssueIds = [];

    /** @var array<string> */
    public array $selectedActionPlanIds = [];

    public int $activeBuilderStep = 1;

    /** @var array<string, bool> */
    public array $collapsedBuilderSteps = [
        '1' => false,
        '2' => false,
        '3' => true,
        '4' => true,
        '5' => true,
    ];

    public string $taskJumpPage = '';
    public string $issueJumpPage = '';
    public string $actionPlanJumpPage = '';
    public string $previewZoom = 'fit';
    public string $previewSyncedFingerprint = '';
    public bool $previewDirty = false;
    public ?string $lastPreviewAt = null;
    public ?string $lastRenderedAt = null;

    /** @var array<string> */
    public array $taskOrderBaseline = [];

    /** @var array<string> */
    public array $issueOrderBaseline = [];

    /** @var array<string> */
    public array $taskOrderUndo = [];

    /** @var array<string> */
    public array $issueOrderUndo = [];

    /** @var array<string> */
    public array $actionPlanOrderBaseline = [];

    /** @var array<string> */
    public array $actionPlanOrderUndo = [];

    protected bool $syncingColumnWidths = false;
    protected string $lastPersistedColumnWidthFingerprint = '';
    public ?int $editingHistoryId = null;
    public ?int $editingRootHistoryId = null;
    public int $editingVersionNo = 1;
    /** @var array<string, string> */
    public array $historyVersionOptions = [];
    public ?string $selectedHistoryVersionId = null;

    public function mount(): void
    {
        $savedColumnWidths = $this->loadSavedColumnWidths();

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
            'column_widths' => $savedColumnWidths,
            'signatures' => [],
        ]);

        $this->taskJumpPage = '1';
        $this->issueJumpPage = '1';
        $this->actionPlanJumpPage = '1';

        $historyId = (int) request()->query('history_id', 0);
        if ($historyId > 0) {
            $this->hydrateFromHistorySnapshot($historyId);
        }

        $this->setPersistedColumnWidthFingerprint(
            data_get($this->formData, 'column_widths', $savedColumnWidths)
        );

        $this->refreshPreviewState();
    }

    public function getTitle(): string
    {
        return $this->editingHistoryId ? 'Edit Report' : 'Create Report';
    }

    /**
     * Disable Filament default header action bar for this page.
     * We already provide custom actions in the preview toolbar.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [];
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

    public function manageColumnWidthsAction(): Action
    {
        return Action::make('manageColumnWidths')
            ->label('Lebar Kolom')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('gray')
            ->modalHeading('Resize Lebar Kolom (Drag Cursor)')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalWidth('4xl')
            ->modalContent(view('filament.pages.partials.report-column-width-controls'));
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
        [$sourceHistoryId, $versionNo] = $this->resolveHistoryVersioningContext();

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
            'source_history_id' => $sourceHistoryId,
            'version_no' => $versionNo,
            'payload' => $this->buildHistoryPayload($data),
        ]);

        if ($this->editingRootHistoryId || $this->editingHistoryId) {
            $this->editingRootHistoryId = (int) ($history->source_history_id ?: $history->id);
            $this->editingHistoryId = (int) $history->id;
            $this->editingVersionNo = max(1, (int) ($history->version_no ?: 1));
            $this->refreshHistoryVersionOptions();
        }

        $url = route('task-report.history.pdf', ['history' => $history]);
        $this->markReportRendered();
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
        [$sourceHistoryId, $versionNo] = $this->resolveHistoryVersioningContext();

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
            'source_history_id' => $sourceHistoryId,
            'version_no' => $versionNo,
            'payload' => $this->buildHistoryPayload($data),
        ]);

        if ($this->editingRootHistoryId || $this->editingHistoryId) {
            $this->editingRootHistoryId = (int) ($history->source_history_id ?: $history->id);
            $this->editingHistoryId = (int) $history->id;
            $this->editingVersionNo = max(1, (int) ($history->version_no ?: 1));
            $this->refreshHistoryVersionOptions();
        }

        $url = route('task-report.history.docx', ['history' => $history]);
        $this->markReportRendered();
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

        $columnWidths = $this->resolveReportColumnWidths($data['columnWidths'] ?? []);
        $wNo = (int) round($totalWidth * ((float) $columnWidths['no'] / 100));
        $wItem = (int) round($totalWidth * ((float) $columnWidths['project_task'] / 100));
        $wPembahasan = (int) round($totalWidth * ((float) $columnWidths['percent'] / 100));
        $wRencana = (int) round($totalWidth * ((float) $columnWidths['issue'] / 100));
        $wTarget = (int) round($totalWidth * ((float) $columnWidths['action_plan'] / 100));
        $wPic = (int) round($totalWidth * ((float) $columnWidths['pic'] / 100));
        $wEvaluasi = $totalWidth - $wNo - $wItem - $wPembahasan - $wRencana - $wTarget - $wPic;

        $dataTable->addRow();
        $dataTable->addCell($wNo)->addText('No', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wItem)->addText('Project & Task', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wPembahasan)->addText('%', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wRencana)->addText('Issue', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wTarget)->addText('Action Plan', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wPic)->addText('PIC', ['bold' => true], ['alignment' => 'center']);
        $dataTable->addCell($wEvaluasi)->addText("Evaluasi\nEfektivitas", ['bold' => true], ['alignment' => 'center']);

        $rows = Arr::wrap($data['previewRows'] ?? []);
        if (empty($rows)) {
            $dataTable->addRow();
            $dataTable->addCell($totalWidth, ['gridSpan' => 7])->addText('Belum ada task dipilih.', ['color' => '6B7280'], ['alignment' => 'center']);
        } else {
            foreach ($rows as $row) {
                $taskStatusKey = $this->normalizeReportStatus((string) ($row['task_status_key'] ?? $row['evaluasi'] ?? ''));
                $taskEvaluasiText = (string) ($row['task_evaluasi'] ?? $this->reportStatusLabel($taskStatusKey));
                $issueEntries = collect($row['issue_entries'] ?? [])
                    ->filter(fn($item) => is_array($item))
                    ->values();
                $hasRealIssues = $issueEntries->isNotEmpty();

                if ($issueEntries->isEmpty()) {
                    $issueEntries = collect([[
                        'issue' => '-',
                        'action_plan' => '-',
                        'status_key' => '',
                        'status_label' => '',
                        'pic' => '-',
                        'has_action_plan' => false,
                    ]]);
                }

                $taskTextColor = match ($taskStatusKey) {
                    'closed' => '166534',
                    'progress' => 'A16207',
                    'opened' => '1D4ED8',
                    'overdue' => 'B91C1C',
                    'postponed' => '4B5563',
                    default => '111827',
                };

                foreach ($issueEntries as $entryIndex => $issueEntry) {
                    $entryStatusKey = $this->normalizeReportStatus((string) ($issueEntry['status_key'] ?? ''));
                    $entryStatusLabel = (string) ($issueEntry['status_label'] ?? '');
                    $issueText = $this->docxPlain((string) ($issueEntry['issue'] ?? '-'));
                    $issueId = (string) ($issueEntry['issue_id'] ?? '');
                    $issueKey = $issueId !== '' ? ('id:' . $issueId) : ('text:' . $issueText);
                    $lastIssueKey = $entryIndex > 0 ? (($issueEntries[$entryIndex - 1]['issue_id'] ?? null) ? ('id:' . (string) $issueEntries[$entryIndex - 1]['issue_id']) : ('text:' . $this->docxPlain((string) ($issueEntries[$entryIndex - 1]['issue'] ?? '-')))) : null;
                    $showIssueText = $entryIndex === 0 || $issueKey !== $lastIssueKey;
                    $entryStatusColor = match ($entryStatusKey) {
                        'closed' => '166534',
                        'progress' => 'A16207',
                        'opened' => '1D4ED8',
                        'overdue' => 'B91C1C',
                        'postponed' => '4B5563',
                        default => '111827',
                    };

                    $dataTable->addRow();

                    if ($entryIndex === 0) {
                        $dataTable->addCell($wNo)->addText((string) ($row['no'] ?? ''), [], ['alignment' => 'center']);
                        $itemCell = $dataTable->addCell($wItem);
                        $projectName = trim((string) ($row['project_name'] ?? ''));
                        $taskName = trim((string) ($row['task_name'] ?? ''));
                        if ($projectName !== '' && $projectName !== '-') {
                            $itemCell->addText($this->docxPlain($projectName), ['bold' => true]);
                            $itemCell->addText($this->docxPlain($taskName !== '' ? $taskName : '-'));
                        } else {
                            $this->addDocxMultilineCell($itemCell, (string) ($row['project_task'] ?? $row['item'] ?? '-'));
                        }
                        $dataTable->addCell($wPembahasan)->addText($this->docxPlain((string) ($row['percent'] ?? '-')), [], ['alignment' => 'center']);
                    } else {
                        $dataTable->addCell($wNo)->addText('');
                        $dataTable->addCell($wItem)->addText('');
                        $dataTable->addCell($wPembahasan)->addText('');
                    }

                    $this->addDocxMultilineCell($dataTable->addCell($wRencana), $showIssueText ? (string) ($issueEntry['issue'] ?? '-') : '');
                    $this->addDocxMultilineCell($dataTable->addCell($wTarget), (string) ($issueEntry['action_plan'] ?? '-'));
                    $dataTable->addCell($wPic)->addText($this->docxPlain((string) ($issueEntry['pic'] ?? '-')), [], ['alignment' => 'center']);

                    $evalCell = $dataTable->addCell($wEvaluasi);
                    if (!$hasRealIssues && $entryIndex === 0) {
                        $evalCell->addText($taskEvaluasiText, ['bold' => true, 'color' => $taskTextColor], ['alignment' => 'center', 'spaceAfter' => 20]);
                    }
                    if ($hasRealIssues && trim($entryStatusLabel) !== '' && trim($entryStatusLabel) !== '-') {
                        $evalCell->addText($entryStatusLabel, ['size' => 9, 'color' => $entryStatusColor], ['alignment' => 'center', 'spaceAfter' => 10]);
                    } elseif ($hasRealIssues && $entryIndex !== 0) {
                        $evalCell->addText('-', ['size' => 9, 'color' => '6B7280'], ['alignment' => 'center']);
                    }
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
        $this->syncTaskOrderBaseline();
        $this->syncIssueOrderBaseline();
        $this->syncActionPlanOrderBaseline();

        $taskPickerData = $this->getTaskPickerData();
        $issuePickerData = $this->getIssuePickerData();
        $actionPlanPickerData = $this->getActionPlanPickerData();
        $reportData = $this->getReportData();

        $this->taskJumpPage = (string) ($taskPickerData['meta']['page'] ?? 1);
        $this->issueJumpPage = (string) ($issuePickerData['meta']['page'] ?? 1);
        $this->actionPlanJumpPage = (string) ($actionPlanPickerData['meta']['page'] ?? 1);
        $this->previewDirty = $this->currentPreviewFingerprint() !== $this->previewSyncedFingerprint;

        $summaryWarnings = $this->buildSummaryWarnings($reportData);
        $stepStates = $this->getBuilderStepStates($summaryWarnings);

        return array_merge([
            'availableTasks' => $taskPickerData['items'],
            'taskPickerMeta' => $taskPickerData['meta'],
            'selectedTasksOrdered' => $this->getSelectedTasksOrdered(),
            'staffFilterOptions' => Staff::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            'projectFilterOptions' => Project::query()->orderBy('project_name')->pluck('project_name', 'id')->toArray(),
            'statusFilterOptions' => Task::query()->select('status')->distinct()->orderBy('status')->pluck('status')->all(),

            'availableIssues' => $issuePickerData['items'],
            'issuePickerMeta' => $issuePickerData['meta'],
            'selectedIssuesOrdered' => $this->getSelectedIssuesOrdered(),
            'issueStatusOptions' => Issue::query()->select('status')->distinct()->orderBy('status')->pluck('status')->all(),
            'issuePriorityOptions' => Issue::query()->select('priority')->distinct()->orderBy('priority')->pluck('priority')->all(),
            'issueStaffOptions' => Staff::query()->orderBy('name')->pluck('name', 'id')->toArray(),

            'availableActionPlans' => $actionPlanPickerData['items'],
            'actionPlanPickerMeta' => $actionPlanPickerData['meta'],
            'selectedActionPlansOrdered' => $this->getSelectedActionPlansOrdered(),
            'actionPlanStatusOptions' => IssueActionPlan::query()->select('status')->distinct()->orderBy('status')->pluck('status')->all(),
            'actionPlanStaffOptions' => Staff::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            'taskFilterChips' => $this->buildTaskFilterChips(),
            'issueFilterChips' => $this->buildIssueFilterChips(),
            'actionPlanFilterChips' => $this->buildActionPlanFilterChips(),
            'summaryWarnings' => $summaryWarnings,
            'builderStepStates' => $stepStates,
            'canAccessIssueStep' => $this->canAccessBuilderStep(3),
            'canAccessActionPlanStep' => $this->canAccessBuilderStep(4),
            'canAccessFinalStep' => $this->canAccessBuilderStep(5),
            'summaryTaskCount' => count($this->selectedTaskIds),
            'summaryIssueCount' => count($this->selectedIssueIds),
            'summaryActionPlanCount' => count($this->selectedActionPlanIds),
            'summaryEstimatedPages' => (int) ($reportData['previewTotalPages'] ?? 1),
            'previewDirty' => $this->previewDirty,
            'lastPreviewAt' => $this->lastPreviewAt,
            'lastRenderedAt' => $this->lastRenderedAt,
            'previewZoom' => $this->previewZoom,
        ], $reportData);
    }

    /**
     * Keep column width total stable at 100% while user drags slider.
     */
    public function updatedFormData(mixed $value, mixed $key): void
    {
        if (!is_string($key) || !Str::startsWith($key, 'column_widths.')) {
            return;
        }

        if ($this->syncingColumnWidths) {
            return;
        }

        $this->syncingColumnWidths = true;
        $this->formData['column_widths'] = $this->resolveReportColumnWidths(
            data_get($this->formData, 'column_widths', [])
        );
        $this->syncingColumnWidths = false;
        $this->persistColumnWidths();
    }

    public function resetColumnWidths(): void
    {
        $this->formData['column_widths'] = $this->defaultColumnWidths();
        $this->persistColumnWidths();
    }

    /**
     * @param array<string, mixed> $widths
     */
    public function applyColumnResize(array $widths): void
    {
        $this->formData['column_widths'] = $this->resolveReportColumnWidths($widths);
        $this->persistColumnWidths();
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
            $this->selectedActionPlanIds = [];
            $this->syncTaskOrderBaseline();
            $this->syncIssueOrderBaseline();
            $this->syncActionPlanOrderBaseline();
            $this->ensureActiveStepIsReachable();
            $this->issuePickerPage = 1;
            $this->actionPlanPickerPage = 1;
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

        $this->syncSelectedActionPlansAgainstSelectedIssues();
        $this->syncTaskOrderBaseline();
        $this->syncIssueOrderBaseline();
        $this->syncActionPlanOrderBaseline();
        $this->ensureActiveStepIsReachable();
        $this->issuePickerPage = 1;
        $this->actionPlanPickerPage = 1;
    }

    public function updatedSelectedIssueIds(): void
    {
        $this->selectedIssueIds = collect($this->selectedIssueIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values()
            ->all();

        $this->syncSelectedActionPlansAgainstSelectedIssues();
        $this->syncIssueOrderBaseline();
        $this->syncActionPlanOrderBaseline();
        $this->ensureActiveStepIsReachable();
        $this->actionPlanPickerPage = 1;
    }

    public function updatedSelectedActionPlanIds(): void
    {
        $this->selectedActionPlanIds = collect($this->selectedActionPlanIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values()
            ->all();

        $this->syncActionPlanOrderBaseline();
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

    public function updatedActionPlanSearch(): void { $this->actionPlanPickerPage = 1; }
    public function updatedActionPlanFilterStatus(): void { $this->actionPlanPickerPage = 1; }
    public function updatedActionPlanFilterStaffId(): void { $this->actionPlanPickerPage = 1; }
    public function updatedShowOnlySelectedActionPlans(): void { $this->actionPlanPickerPage = 1; }
    // endregion

    // region: builder step controls
    public function openBuilderStep(int $step): void
    {
        if (!$this->canAccessBuilderStep($step)) {
            return;
        }

        $this->activeBuilderStep = $step;
        $this->collapsedBuilderSteps[(string) $step] = false;
    }

    public function toggleBuilderStep(int $step): void
    {
        if (!$this->canAccessBuilderStep($step)) {
            return;
        }

        $key = (string) $step;
        $isCollapsed = (bool) ($this->collapsedBuilderSteps[$key] ?? false);
        $this->collapsedBuilderSteps[$key] = !$isCollapsed;

        if (!$this->collapsedBuilderSteps[$key]) {
            $this->activeBuilderStep = $step;
        }
    }

    public function refreshPreviewState(): void
    {
        $this->previewSyncedFingerprint = $this->currentPreviewFingerprint();
        $this->lastPreviewAt = $this->uiTimestamp();
        $this->previewDirty = false;
    }

    public function setPreviewZoom(string $zoom): void
    {
        if (!in_array($zoom, ['fit', '1', '0.75'], true)) {
            return;
        }

        $this->previewZoom = $zoom;
    }
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

    public function goToTaskPickerPage(): void
    {
        $requested = (int) trim($this->taskJumpPage);
        $lastPage = $this->resolveTaskPickerLastPage();
        $this->taskPickerPage = max(1, min($requested, $lastPage));
        $this->taskJumpPage = (string) $this->taskPickerPage;
    }

    public function selectCurrentPageTasks(): void
    {
        $ids = $this->getTaskPickerData()['items']->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->selectedTaskIds = array_values(array_unique([...$this->selectedTaskIds, ...$ids]));
        $this->updatedSelectedTaskIds();
    }

    public function selectFilteredTasks(): void
    {
        $ids = (clone $this->buildTaskPickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedTaskIds = array_values(array_unique([...$this->selectedTaskIds, ...$ids]));
        $this->updatedSelectedTaskIds();
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
        $this->updatedSelectedTaskIds();
    }

    public function clearSelectedTasks(): void
    {
        $this->selectedTaskIds = [];
        $this->selectedIssueIds = [];
        $this->selectedActionPlanIds = [];
        $this->updatedSelectedTaskIds();
        $this->updatedSelectedIssueIds();
        $this->updatedSelectedActionPlanIds();
    }

    /** @param array<int, string|int> $orderedIds */
    public function reorderSelectedTasks(array $orderedIds): void
    {
        $this->rememberTaskOrderForUndo();
        $this->selectedTaskIds = $this->applyExplicitOrder($this->selectedTaskIds, $orderedIds);
    }

    public function moveTaskSelectionUp(string|int $taskId): void
    {
        $this->rememberTaskOrderForUndo();
        $this->selectedTaskIds = $this->moveIdPosition($this->selectedTaskIds, (string) $taskId, -1);
    }

    public function moveTaskSelectionDown(string|int $taskId): void
    {
        $this->rememberTaskOrderForUndo();
        $this->selectedTaskIds = $this->moveIdPosition($this->selectedTaskIds, (string) $taskId, 1);
    }

    public function resetTaskOrder(): void
    {
        if (empty($this->selectedTaskIds)) {
            return;
        }

        $this->rememberTaskOrderForUndo();
        $this->selectedTaskIds = $this->applyExplicitOrder($this->selectedTaskIds, $this->taskOrderBaseline);
    }

    public function sortSelectedTasksByName(): void
    {
        $taskIds = $this->getSelectedTaskIdsAsInt();
        if (empty($taskIds)) {
            return;
        }

        $this->rememberTaskOrderForUndo();
        $ordered = Task::query()
            ->whereIn('id', $taskIds)
            ->orderBy('task_name')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedTaskIds = $this->applyExplicitOrder($this->selectedTaskIds, $ordered);
    }

    public function sortSelectedTasksByTargetDate(): void
    {
        $taskIds = $this->getSelectedTaskIdsAsInt();
        if (empty($taskIds)) {
            return;
        }

        $this->rememberTaskOrderForUndo();
        $ordered = Task::query()
            ->whereIn('id', $taskIds)
            ->orderByRaw('tanggal IS NULL')
            ->orderBy('tanggal')
            ->orderBy('task_name')
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedTaskIds = $this->applyExplicitOrder($this->selectedTaskIds, $ordered);
    }

    public function undoTaskOrder(): void
    {
        if (empty($this->taskOrderUndo)) {
            return;
        }

        $this->selectedTaskIds = $this->applyExplicitOrder($this->selectedTaskIds, $this->taskOrderUndo);
        $this->taskOrderUndo = [];
    }

    public function removeSelectedTask(string|int $taskId): void
    {
        $this->rememberTaskOrderForUndo();
        $taskId = (string) $taskId;

        $this->selectedTaskIds = array_values(array_filter(
            $this->selectedTaskIds,
            fn($id) => (string) $id !== $taskId
        ));

        $remainingTaskIds = $this->getSelectedTaskIdsAsInt();
        if (empty($remainingTaskIds)) {
            $this->selectedIssueIds = [];
            $this->selectedActionPlanIds = [];
            $this->syncIssueOrderBaseline();
            $this->syncActionPlanOrderBaseline();
            $this->ensureActiveStepIsReachable();
            return;
        }

        $allowedIssueIds = Issue::query()
            ->whereIn('task_id', $remainingTaskIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $allowedMap = array_fill_keys($allowedIssueIds, true);
        $this->selectedIssueIds = array_values(array_filter(
            $this->selectedIssueIds,
            fn($id) => isset($allowedMap[(string) $id])
        ));
        $this->syncSelectedActionPlansAgainstSelectedIssues();
        $this->syncTaskOrderBaseline();
        $this->syncIssueOrderBaseline();
        $this->syncActionPlanOrderBaseline();
        $this->ensureActiveStepIsReachable();
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

    public function goToIssuePickerPage(): void
    {
        $requested = (int) trim($this->issueJumpPage);
        $lastPage = $this->resolveIssuePickerLastPage();
        $this->issuePickerPage = max(1, min($requested, $lastPage));
        $this->issueJumpPage = (string) $this->issuePickerPage;
    }

    public function selectCurrentPageIssues(): void
    {
        $ids = $this->getIssuePickerData()['items']->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->selectedIssueIds = array_values(array_unique([...$this->selectedIssueIds, ...$ids]));
        $this->updatedSelectedIssueIds();
    }

    public function selectFilteredIssues(): void
    {
        $ids = (clone $this->buildIssuePickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedIssueIds = array_values(array_unique([...$this->selectedIssueIds, ...$ids]));
        $this->updatedSelectedIssueIds();
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
        $this->updatedSelectedIssueIds();
    }

    public function clearSelectedIssues(): void
    {
        $this->selectedIssueIds = [];
        $this->selectedActionPlanIds = [];
        $this->updatedSelectedIssueIds();
        $this->updatedSelectedActionPlanIds();
    }

    /** @param array<int, string|int> $orderedIds */
    public function reorderSelectedIssues(array $orderedIds): void
    {
        $this->rememberIssueOrderForUndo();
        $this->selectedIssueIds = $this->applyExplicitOrder($this->selectedIssueIds, $orderedIds);
    }

    public function moveIssueSelectionUp(string|int $issueId): void
    {
        $this->rememberIssueOrderForUndo();
        $this->selectedIssueIds = $this->moveIdPosition($this->selectedIssueIds, (string) $issueId, -1);
    }

    public function moveIssueSelectionDown(string|int $issueId): void
    {
        $this->rememberIssueOrderForUndo();
        $this->selectedIssueIds = $this->moveIdPosition($this->selectedIssueIds, (string) $issueId, 1);
    }

    public function resetIssueOrder(): void
    {
        if (empty($this->selectedIssueIds)) {
            return;
        }

        $this->rememberIssueOrderForUndo();
        $this->selectedIssueIds = $this->applyExplicitOrder($this->selectedIssueIds, $this->issueOrderBaseline);
    }

    public function sortSelectedIssuesByName(): void
    {
        $issueIds = collect($this->selectedIssueIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (empty($issueIds)) {
            return;
        }

        $this->rememberIssueOrderForUndo();
        $ordered = Issue::query()
            ->whereIn('id', $issueIds)
            ->orderBy('issue_name')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedIssueIds = $this->applyExplicitOrder($this->selectedIssueIds, $ordered);
    }

    public function undoIssueOrder(): void
    {
        if (empty($this->issueOrderUndo)) {
            return;
        }

        $this->selectedIssueIds = $this->applyExplicitOrder($this->selectedIssueIds, $this->issueOrderUndo);
        $this->issueOrderUndo = [];
    }

    public function removeSelectedIssue(string|int $issueId): void
    {
        $this->rememberIssueOrderForUndo();
        $issueId = (string) $issueId;

        $this->selectedIssueIds = array_values(array_filter(
            $this->selectedIssueIds,
            fn($id) => (string) $id !== $issueId
        ));
        $this->updatedSelectedIssueIds();
    }
    // endregion

    // region: action plan picker actions
    public function nextActionPlanPickerPage(): void
    {
        if ($this->actionPlanPickerPage < $this->resolveActionPlanPickerLastPage()) {
            $this->actionPlanPickerPage++;
        }
    }

    public function previousActionPlanPickerPage(): void
    {
        if ($this->actionPlanPickerPage > 1) {
            $this->actionPlanPickerPage--;
        }
    }

    public function goToActionPlanPickerPage(): void
    {
        $requested = (int) trim($this->actionPlanJumpPage);
        $lastPage = $this->resolveActionPlanPickerLastPage();
        $this->actionPlanPickerPage = max(1, min($requested, $lastPage));
        $this->actionPlanJumpPage = (string) $this->actionPlanPickerPage;
    }

    public function selectCurrentPageActionPlans(): void
    {
        $ids = $this->getActionPlanPickerData()['items']->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->selectedActionPlanIds = array_values(array_unique([...$this->selectedActionPlanIds, ...$ids]));
        $this->updatedSelectedActionPlanIds();
    }

    public function selectFilteredActionPlans(): void
    {
        $ids = (clone $this->buildActionPlanPickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedActionPlanIds = array_values(array_unique([...$this->selectedActionPlanIds, ...$ids]));
        $this->updatedSelectedActionPlanIds();
    }

    public function unselectFilteredActionPlans(): void
    {
        $filteredIds = (clone $this->buildActionPlanPickerQuery())
            ->limit(2000)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $filteredMap = array_fill_keys($filteredIds, true);

        $this->selectedActionPlanIds = array_values(array_filter(
            $this->selectedActionPlanIds,
            fn($id) => !isset($filteredMap[(string) $id])
        ));
        $this->updatedSelectedActionPlanIds();
    }

    public function clearSelectedActionPlans(): void
    {
        $this->selectedActionPlanIds = [];
        $this->updatedSelectedActionPlanIds();
    }

    /** @param array<int, string|int> $orderedIds */
    public function reorderSelectedActionPlans(array $orderedIds): void
    {
        $this->rememberActionPlanOrderForUndo();
        $this->selectedActionPlanIds = $this->applyExplicitOrder($this->selectedActionPlanIds, $orderedIds);
    }

    public function moveActionPlanSelectionUp(string|int $actionPlanId): void
    {
        $this->rememberActionPlanOrderForUndo();
        $this->selectedActionPlanIds = $this->moveIdPosition($this->selectedActionPlanIds, (string) $actionPlanId, -1);
    }

    public function moveActionPlanSelectionDown(string|int $actionPlanId): void
    {
        $this->rememberActionPlanOrderForUndo();
        $this->selectedActionPlanIds = $this->moveIdPosition($this->selectedActionPlanIds, (string) $actionPlanId, 1);
    }

    public function resetActionPlanOrder(): void
    {
        if (empty($this->selectedActionPlanIds)) {
            return;
        }

        $this->rememberActionPlanOrderForUndo();
        $this->selectedActionPlanIds = $this->applyExplicitOrder($this->selectedActionPlanIds, $this->actionPlanOrderBaseline);
    }

    public function sortSelectedActionPlansByDescription(): void
    {
        $actionPlanIds = collect($this->selectedActionPlanIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (empty($actionPlanIds)) {
            return;
        }

        $this->rememberActionPlanOrderForUndo();
        $ordered = IssueActionPlan::query()
            ->whereIn('id', $actionPlanIds)
            ->orderBy('description')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedActionPlanIds = $this->applyExplicitOrder($this->selectedActionPlanIds, $ordered);
    }

    public function sortSelectedActionPlansByIssue(): void
    {
        $actionPlanIds = collect($this->selectedActionPlanIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (empty($actionPlanIds)) {
            return;
        }

        $this->rememberActionPlanOrderForUndo();
        $ordered = IssueActionPlan::query()
            ->with('issue:id,issue_name')
            ->whereIn('id', $actionPlanIds)
            ->get()
            ->sortBy(function (IssueActionPlan $actionPlan): string {
                return Str::lower((string) ($actionPlan->issue?->issue_name ?? '')) . '|' . (string) $actionPlan->id;
            })
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $this->selectedActionPlanIds = $this->applyExplicitOrder($this->selectedActionPlanIds, $ordered);
    }

    public function undoActionPlanOrder(): void
    {
        if (empty($this->actionPlanOrderUndo)) {
            return;
        }

        $this->selectedActionPlanIds = $this->applyExplicitOrder($this->selectedActionPlanIds, $this->actionPlanOrderUndo);
        $this->actionPlanOrderUndo = [];
    }

    public function removeSelectedActionPlan(string|int $actionPlanId): void
    {
        $this->rememberActionPlanOrderForUndo();
        $actionPlanId = (string) $actionPlanId;

        $this->selectedActionPlanIds = array_values(array_filter(
            $this->selectedActionPlanIds,
            fn($id) => (string) $id !== $actionPlanId
        ));
        $this->updatedSelectedActionPlanIds();
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
        $availableUnitsPerPage = 42;
        $usedUnits = $this->estimateContentUnits($rows);

        return max(1, (int) ceil($usedUnits / $availableUnitsPerPage));
    }

    protected function estimateContentUnits(array $rows): int
    {
        if (empty($rows)) {
            return 1;
        }

        $usedUnits = 0;

        foreach ($rows as $row) {
            $usedUnits += $this->estimateRowUnits($row);
        }

        return $usedUnits;
    }

    protected function getReportData(): array
    {
        $previewRows = $this->getPreviewRows();
        $signatures = $this->formatSignatures(data_get($this->formData, 'signatures', []));
        $columnWidths = $this->resolveReportColumnWidths(data_get($this->formData, 'column_widths', []));
        $contentUnits = $this->estimateContentUnits($previewRows);
        $basePages = $this->estimateTotalPages($previewRows);
        $previewPages = $this->buildPreviewPages($previewRows, $signatures);

        $unitsPerPage = 42;
        $signatureUnits = empty($signatures) ? 0 : 9; // tinggi blok tanda tangan
        $lastPageUsedUnits = $contentUnits % $unitsPerPage;
        $lastPageUsedUnits = $lastPageUsedUnits === 0 ? $unitsPerPage : $lastPageUsedUnits;
        $freeUnitsOnLastPage = $unitsPerPage - $lastPageUsedUnits;

        // Biarkan tetap di halaman saat masih cukup ruang.
        // Pindah halaman hanya jika benar-benar tidak cukup untuk blok tanda tangan.
        $signaturePageBreakBefore = !empty($signatures) && ($freeUnitsOnLastPage < $signatureUnits);
        $totalPages = $basePages + ($signaturePageBreakBefore ? 1 : 0);

        $signaturePushPx = 0;
        if (!empty($signatures)) {
            if ($signaturePageBreakBefore) {
                // Halaman baru khusus tanda tangan: dorong ke bawah (dekat kanan-bawah halaman).
                $signaturePushPx = max(680, (int) (($unitsPerPage - $signatureUnits) * 22));
            } else {
                // Tetap di halaman yang sama: dorong seperlunya mengikuti ruang tersisa.
                $signaturePushUnits = max(0, $freeUnitsOnLastPage - $signatureUnits);
                $signaturePushPx = ($signaturePushUnits * 14);
            }

            // Fine tune: turunkan posisi blok tanda tangan sedikit lagi.
            $signaturePushPx += 20;
        }

        return [
            'previewRows' => $previewRows,
            'previewPages' => $previewPages,
            'previewTotalPages' => count($previewPages),
            'columnWidths' => $columnWidths,
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
            'signatures' => $signatures,
            'signaturePageBreakBefore' => $signaturePageBreakBefore,
            'signaturePushPx' => $signaturePushPx,
            'pageLabel' => "1 dari {$totalPages}",
            'logoSrc' => asset('images/logo_report.png'),
        ];
    }

    protected function estimateRowUnits(array $row): int
    {
        $projectTask = (string) ($row['project_task'] ?? $row['item'] ?? '');
        $taskPercent = (string) ($row['percent'] ?? '');
        $issueEntries = collect($row['issue_entries'] ?? [])->filter(fn($item) => is_array($item));

        $taskLineUnits = max(
            1,
            (int) ceil(mb_strlen($projectTask) / 28),
            (int) ceil(mb_strlen($taskPercent) / 8),
        );

        $issueUnits = $issueEntries->sum(function (array $issue): int {
            $issueName = (string) ($issue['issue'] ?? '');
            $actionPlan = (string) ($issue['action_plan'] ?? '');

            return max(
                1,
                (int) ceil(mb_strlen($issueName) / 24),
                (int) ceil(mb_strlen($actionPlan) / 52),
            );
        });

        return $taskLineUnits + max(1, $issueUnits) + 1;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{name:string,company_or_role:string}>  $signatures
     * @return array<int, array{rows:array<int, array<string,mixed>>,showCover:bool,showSignatures:bool,signaturePushPx:int,pageNumber:int,totalPages:int}>
     */
    protected function buildPreviewPages(array $rows, array $signatures): array
    {
        // Keep preview pagination consistent with report estimation logic.
        $firstPageCapacity = 42;
        $nextPageCapacity = 42;
        $signatureUnits = empty($signatures) ? 0 : 9;

        $pages = [];
        $currentRows = [];
        $usedUnits = 0;
        $capacity = $firstPageCapacity;

        foreach ($rows as $row) {
            $rowUnits = $this->estimateRowUnits($row);

            if (!empty($currentRows) && ($usedUnits + $rowUnits) > $capacity) {
                $pages[] = [
                    'rows' => $currentRows,
                    'used_units' => $usedUnits,
                    'capacity' => $capacity,
                ];
                $currentRows = [];
                $usedUnits = 0;
                $capacity = $nextPageCapacity;
            }

            $currentRows[] = $row;
            $usedUnits += $rowUnits;
        }

        $pages[] = [
            'rows' => $currentRows,
            'used_units' => $usedUnits,
            'capacity' => $capacity,
        ];

        if (empty($rows)) {
            $pages[0]['rows'] = [];
            $pages[0]['used_units'] = 1;
        }

        $lastIndex = count($pages) - 1;
        $freeUnitsLastPage = max(0, (int) (($pages[$lastIndex]['capacity'] ?? $nextPageCapacity) - ($pages[$lastIndex]['used_units'] ?? 0)));
        $signatureOnNewPage = !empty($signatures) && ($freeUnitsLastPage < $signatureUnits);

        if ($signatureOnNewPage) {
            $pages[] = [
                'rows' => [],
                'used_units' => 0,
                'capacity' => $nextPageCapacity,
            ];
            $lastIndex = count($pages) - 1;
        }

        $totalPages = count($pages);

        return collect($pages)
            ->values()
            ->map(function (array $page, int $index) use ($lastIndex, $totalPages, $signatureUnits): array {
                $showSignatures = $index === $lastIndex;
                $capacity = (int) ($page['capacity'] ?? 40);
                $used = (int) ($page['used_units'] ?? 0);
                $freeUnits = max(0, $capacity - $used);
                $pushUnits = $showSignatures ? max(0, $freeUnits - $signatureUnits) : 0;
                $pushPx = $showSignatures ? ($pushUnits * 14) : 0;
                if ($showSignatures) {
                    $pushPx += 20;
                }

                return [
                    'rows' => Arr::wrap($page['rows'] ?? []),
                    'showCover' => $index === 0,
                    'showSignatures' => $showSignatures,
                    'signaturePushPx' => $pushPx,
                    'pageNumber' => $index + 1,
                    'totalPages' => $totalPages,
                ];
            })
            ->all();
    }

    protected function canAccessBuilderStep(int $step): bool
    {
        $hasTask = count($this->selectedTaskIds) > 0;
        $hasIssue = count($this->selectedIssueIds) > 0;

        return match ($step) {
            1, 2 => true,
            3 => $hasTask,
            4 => $hasIssue,
            5 => $hasTask,
            default => false,
        };
    }

    protected function ensureActiveStepIsReachable(): void
    {
        if (!$this->canAccessBuilderStep($this->activeBuilderStep)) {
            if (count($this->selectedTaskIds) === 0) {
                $this->activeBuilderStep = 1;
                return;
            }

            $this->activeBuilderStep = count($this->selectedIssueIds) > 0 ? 4 : 3;
        }
    }

    protected function getBuilderStepStates(array $warnings = []): array
    {
        $hasTask = count($this->selectedTaskIds) > 0;
        $hasIssue = count($this->selectedIssueIds) > 0;
        $hasBlockingWarning = collect($warnings)->contains(fn(string $warning) => str_contains($warning, 'wajib'));
        $isStepOneComplete = !$hasBlockingWarning;

        return [
            1 => [
                'title' => 'Informasi Rapat',
                'description' => 'Isi metadata dokumen dan informasi rapat.',
                'enabled' => true,
                'complete' => $isStepOneComplete,
            ],
            2 => [
                'title' => 'Pilih Task',
                'description' => 'Cari, filter, dan pilih task sumber laporan.',
                'enabled' => true,
                'complete' => $hasTask,
            ],
            3 => [
                'title' => 'Pilih Issue',
                'description' => null,
                'enabled' => $hasTask,
                'complete' => $hasTask && $hasIssue,
            ],
            4 => [
                'title' => 'Pilih Action Plan',
                'description' => null,
                'enabled' => $hasIssue,
                'complete' => $hasIssue,
            ],
            5 => [
                'title' => 'Urutan & Final Check',
                'description' => null,
                'enabled' => $hasTask,
                'complete' => $hasTask && !$this->previewDirty,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $reportData
     * @return array<int, string>
     */
    protected function buildSummaryWarnings(array $reportData): array
    {
        $warnings = [];

        if (blank((string) data_get($this->formData, 'title_id', ''))) {
            $warnings[] = 'Judul (ID) wajib diisi.';
        }

        if (blank((string) data_get($this->formData, 'title_en', ''))) {
            $warnings[] = 'Judul (EN) wajib diisi.';
        }

        if (blank((string) data_get($this->formData, 'meeting_day', ''))) {
            $warnings[] = 'Hari rapat belum diisi.';
        }

        if (blank((string) data_get($this->formData, 'meeting_time', ''))) {
            $warnings[] = 'Waktu rapat belum diisi.';
        }

        if (count($this->selectedTaskIds) === 0) {
            $warnings[] = 'Belum ada task dipilih.';
        }

        if (count($this->selectedTaskIds) > 0 && count($this->selectedIssueIds) === 0) {
            $warnings[] = 'Tidak ada issue terpilih (opsional).';
        }

        if ((int) ($reportData['previewTotalPages'] ?? 1) > 3) {
            $warnings[] = 'Estimasi halaman cukup panjang, pastikan urutan sudah optimal.';
        }

        return $warnings;
    }

    /**
     * @return array<int, string>
     */
    protected function buildTaskFilterChips(): array
    {
        $chips = [];

        if (trim($this->taskSearch) !== '') {
            $chips[] = 'Cari: "' . trim($this->taskSearch) . '"';
        }

        if ($this->taskFilterStaffId) {
            $name = Staff::query()->whereKey($this->taskFilterStaffId)->value('name');
            $chips[] = 'PIC: ' . ($name ?: $this->taskFilterStaffId);
        }

        if ($this->taskFilterProjectId) {
            $name = Project::query()->whereKey($this->taskFilterProjectId)->value('project_name');
            $chips[] = 'Project: ' . ($name ?: $this->taskFilterProjectId);
        }

        if ($this->taskFilterStatus !== '') {
            $chips[] = 'Status: ' . ucfirst(str_replace('_', ' ', $this->taskFilterStatus));
        }

        if ($this->showOnlySelectedTasks) {
            $chips[] = 'Selected only';
        }

        return $chips;
    }

    /**
     * @return array<int, string>
     */
    protected function buildIssueFilterChips(): array
    {
        $chips = [];

        if (trim($this->issueSearch) !== '') {
            $chips[] = 'Cari: "' . trim($this->issueSearch) . '"';
        }

        if ($this->issueFilterStatus !== '') {
            $chips[] = 'Status: ' . ucfirst(str_replace('_', ' ', $this->issueFilterStatus));
        }

        if ($this->issueFilterPriority !== '') {
            $chips[] = 'Priority: ' . ucfirst(str_replace('_', ' ', $this->issueFilterPriority));
        }

        if ($this->issueFilterStaffId) {
            $name = Staff::query()->whereKey($this->issueFilterStaffId)->value('name');
            $chips[] = 'PIC: ' . ($name ?: $this->issueFilterStaffId);
        }

        if ($this->showOnlySelectedIssues) {
            $chips[] = 'Selected only';
        }

        return $chips;
    }

    /**
     * @return array<int, string>
     */
    protected function buildActionPlanFilterChips(): array
    {
        $chips = [];

        if (trim($this->actionPlanSearch) !== '') {
            $chips[] = 'Cari: "' . trim($this->actionPlanSearch) . '"';
        }

        if ($this->actionPlanFilterStatus !== '') {
            $chips[] = 'Status: ' . ucfirst(str_replace('_', ' ', $this->actionPlanFilterStatus));
        }

        if ($this->actionPlanFilterStaffId) {
            $name = Staff::query()->whereKey($this->actionPlanFilterStaffId)->value('name');
            $chips[] = 'PIC: ' . ($name ?: $this->actionPlanFilterStaffId);
        }

        if ($this->showOnlySelectedActionPlans) {
            $chips[] = 'Selected only';
        }

        return $chips;
    }

    protected function syncTaskOrderBaseline(): void
    {
        $selected = collect($this->selectedTaskIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values();

        $baseline = collect($this->taskOrderBaseline)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $selected->contains($id))
            ->values();

        $newIds = $selected
            ->reject(fn($id) => $baseline->contains($id))
            ->values();

        $this->taskOrderBaseline = $baseline
            ->concat($newIds)
            ->values()
            ->all();
    }

    protected function syncIssueOrderBaseline(): void
    {
        $selected = collect($this->selectedIssueIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values();

        $baseline = collect($this->issueOrderBaseline)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $selected->contains($id))
            ->values();

        $newIds = $selected
            ->reject(fn($id) => $baseline->contains($id))
            ->values();

        $this->issueOrderBaseline = $baseline
            ->concat($newIds)
            ->values()
            ->all();
    }

    protected function syncActionPlanOrderBaseline(): void
    {
        $selected = collect($this->selectedActionPlanIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values();

        $baseline = collect($this->actionPlanOrderBaseline)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $selected->contains($id))
            ->values();

        $newIds = $selected
            ->reject(fn($id) => $baseline->contains($id))
            ->values();

        $this->actionPlanOrderBaseline = $baseline
            ->concat($newIds)
            ->values()
            ->all();
    }

    protected function rememberTaskOrderForUndo(): void
    {
        $this->taskOrderUndo = collect($this->selectedTaskIds)
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();
    }

    protected function rememberIssueOrderForUndo(): void
    {
        $this->issueOrderUndo = collect($this->selectedIssueIds)
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();
    }

    protected function rememberActionPlanOrderForUndo(): void
    {
        $this->actionPlanOrderUndo = collect($this->selectedActionPlanIds)
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();
    }

    protected function syncSelectedActionPlansAgainstSelectedIssues(): void
    {
        $selectedIssueIds = $this->getSelectedIssueIdsAsInt();

        if (empty($selectedIssueIds)) {
            $this->selectedActionPlanIds = [];
            return;
        }

        $allowedActionPlanIds = IssueActionPlan::query()
            ->whereIn('issue_id', $selectedIssueIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();

        $allowedMap = array_fill_keys($allowedActionPlanIds, true);
        $this->selectedActionPlanIds = array_values(array_filter(
            $this->selectedActionPlanIds,
            fn($id) => isset($allowedMap[(string) $id])
        ));
    }

    protected function hydrateFromHistorySnapshot(int $historyId): void
    {
        $history = ReportHistory::query()->find($historyId);

        if (!$history) {
            Notification::make()
                ->title('History tidak ditemukan')
                ->body('Data report history yang dipilih tidak tersedia.')
                ->danger()
                ->send();
            $this->historyVersionOptions = [];
            $this->selectedHistoryVersionId = null;
            return;
        }

        if ((int) $history->printed_by !== (int) auth()->id()) {
            Notification::make()
                ->title('Akses ditolak')
                ->body('Anda tidak memiliki akses untuk mengedit history ini.')
                ->danger()
                ->send();

            $this->redirect(static::getUrl(), navigate: true);
            return;
        }

        $this->editingHistoryId = (int) $history->id;
        $this->editingRootHistoryId = (int) ($history->source_history_id ?: $history->id);
        $this->editingVersionNo = max(1, (int) ($history->version_no ?: 1));
        $this->selectedHistoryVersionId = (string) $this->editingHistoryId;

        $payload = is_array($history->payload) ? $history->payload : [];

        $formData = data_get($payload, 'form_data', []);
        if (is_array($formData) && !empty($formData)) {
            $merged = array_replace($this->formData ?? [], $formData);
            if (is_array(data_get($merged, 'column_widths'))) {
                $merged['column_widths'] = $this->resolveReportColumnWidths((array) $merged['column_widths']);
            }
            $this->form->fill($merged);
        }

        $selectedTaskIds = Arr::wrap(data_get($payload, 'selected_task_ids', []));
        $selectedIssueIds = Arr::wrap(data_get($payload, 'selected_issue_ids', []));
        $selectedActionPlanIds = Arr::wrap(data_get($payload, 'selected_action_plan_ids', []));

        [$this->selectedTaskIds, $missingTasks] = $this->filterExistingIds(
            $selectedTaskIds,
            Task::query()
        );
        [$this->selectedIssueIds, $missingIssues] = $this->filterExistingIds(
            $selectedIssueIds,
            Issue::query()
        );
        [$this->selectedActionPlanIds, $missingActionPlans] = $this->filterExistingIds(
            $selectedActionPlanIds,
            IssueActionPlan::query()
        );

        $selectedTaskIdsAsInt = $this->getSelectedTaskIdsAsInt();
        if (!empty($selectedTaskIdsAsInt)) {
            $allowedIssueIds = Issue::query()
                ->whereIn('task_id', $selectedTaskIdsAsInt)
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->all();

            $allowedIssueMap = array_fill_keys($allowedIssueIds, true);
            $issueCountBeforeRelationFilter = count($this->selectedIssueIds);
            $this->selectedIssueIds = array_values(array_filter(
                $this->selectedIssueIds,
                fn($id) => isset($allowedIssueMap[(string) $id])
            ));
            $removedByRelation = max(0, $issueCountBeforeRelationFilter - count($this->selectedIssueIds));
        } else {
            $this->selectedIssueIds = [];
            $this->selectedActionPlanIds = [];
            $removedByRelation = 0;
        }

        $taskOrder = Arr::wrap(data_get($payload, 'task_order_baseline', $this->selectedTaskIds));
        $issueOrder = Arr::wrap(data_get($payload, 'issue_order_baseline', $this->selectedIssueIds));
        $actionPlanOrder = Arr::wrap(data_get($payload, 'action_plan_order_baseline', $this->selectedActionPlanIds));

        $this->taskOrderBaseline = $this->applyExplicitOrder($this->selectedTaskIds, $taskOrder);
        $this->issueOrderBaseline = $this->applyExplicitOrder($this->selectedIssueIds, $issueOrder);
        $this->actionPlanOrderBaseline = $this->applyExplicitOrder($this->selectedActionPlanIds, $actionPlanOrder);

        $state = is_array(data_get($payload, 'state')) ? data_get($payload, 'state') : [];
        $this->taskSearch = (string) ($state['task_search'] ?? $this->taskSearch);
        $this->taskFilterStaffId = filled($state['task_filter_staff_id'] ?? null) ? (int) $state['task_filter_staff_id'] : null;
        $this->taskFilterProjectId = filled($state['task_filter_project_id'] ?? null) ? (int) $state['task_filter_project_id'] : null;
        $this->taskFilterStatus = (string) ($state['task_filter_status'] ?? $this->taskFilterStatus);
        $this->showOnlySelectedTasks = (bool) ($state['show_only_selected_tasks'] ?? $this->showOnlySelectedTasks);
        $this->taskPickerPage = max(1, (int) ($state['task_picker_page'] ?? $this->taskPickerPage));

        $this->issueSearch = (string) ($state['issue_search'] ?? $this->issueSearch);
        $this->issueFilterStatus = (string) ($state['issue_filter_status'] ?? $this->issueFilterStatus);
        $this->issueFilterPriority = (string) ($state['issue_filter_priority'] ?? $this->issueFilterPriority);
        $this->issueFilterStaffId = filled($state['issue_filter_staff_id'] ?? null) ? (int) $state['issue_filter_staff_id'] : null;
        $this->showOnlySelectedIssues = (bool) ($state['show_only_selected_issues'] ?? $this->showOnlySelectedIssues);
        $this->issuePickerPage = max(1, (int) ($state['issue_picker_page'] ?? $this->issuePickerPage));

        $this->actionPlanSearch = (string) ($state['action_plan_search'] ?? $this->actionPlanSearch);
        $this->actionPlanFilterStatus = (string) ($state['action_plan_filter_status'] ?? $this->actionPlanFilterStatus);
        $this->actionPlanFilterStaffId = filled($state['action_plan_filter_staff_id'] ?? null) ? (int) $state['action_plan_filter_staff_id'] : null;
        $this->showOnlySelectedActionPlans = (bool) ($state['show_only_selected_action_plans'] ?? $this->showOnlySelectedActionPlans);
        $this->actionPlanPickerPage = max(1, (int) ($state['action_plan_picker_page'] ?? $this->actionPlanPickerPage));
        $this->activeBuilderStep = max(1, min(5, (int) ($state['active_builder_step'] ?? $this->activeBuilderStep)));

        $collapsed = is_array($state['collapsed_builder_steps'] ?? null) ? $state['collapsed_builder_steps'] : [];
        foreach (['1', '2', '3', '4', '5'] as $stepKey) {
            if (array_key_exists($stepKey, $collapsed)) {
                $this->collapsedBuilderSteps[$stepKey] = (bool) $collapsed[$stepKey];
            }
        }

        $savedZoom = (string) ($state['preview_zoom'] ?? $this->previewZoom);
        if (in_array($savedZoom, ['fit', '1', '0.75'], true)) {
            $this->previewZoom = $savedZoom;
        }

        $this->syncSelectedActionPlansAgainstSelectedIssues();
        $this->syncTaskOrderBaseline();
        $this->syncIssueOrderBaseline();
        $this->syncActionPlanOrderBaseline();
        $this->ensureActiveStepIsReachable();
        $this->refreshHistoryVersionOptions();

        $missingTotal = $missingTasks + $missingIssues + $missingActionPlans + $removedByRelation;
        if ($missingTotal > 0) {
            Notification::make()
                ->title('Sebagian data lama tidak tersedia')
                ->body("{$missingTotal} item dari snapshot lama dilewati karena sudah tidak tersedia.")
                ->warning()
                ->send();
        } else {
            Notification::make()
                ->title('Mode Edit History aktif')
                ->success()
                ->send();
        }
    }

    public function updatedSelectedHistoryVersionId(?string $historyId): void
    {
        $historyId = trim((string) $historyId);

        if ($historyId === '' || !ctype_digit($historyId)) {
            return;
        }

        $targetId = (int) $historyId;

        if ($this->editingHistoryId !== null && $targetId === (int) $this->editingHistoryId) {
            return;
        }

        $this->redirect(static::getUrl(['history_id' => $targetId]), navigate: true);
    }

    protected function refreshHistoryVersionOptions(): void
    {
        if (!$this->editingRootHistoryId) {
            $this->historyVersionOptions = [];
            $this->selectedHistoryVersionId = null;
            return;
        }

        $rootId = (int) $this->editingRootHistoryId;

        $versions = ReportHistory::query()
            ->where(function (Builder $query) use ($rootId): void {
                $query
                    ->where('id', $rootId)
                    ->orWhere('source_history_id', $rootId);
            })
            ->orderByDesc('version_no')
            ->orderByDesc('printed_at')
            ->get(['id', 'version_no', 'printed_at']);

        $this->historyVersionOptions = $versions
            ->mapWithKeys(fn(ReportHistory $version) => [
                (string) $version->id => sprintf(
                    'V%s - %s',
                    max(1, (int) ($version->version_no ?: 1)),
                    optional($version->printed_at)->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-'
                ),
            ])
            ->all();

        if ($this->editingHistoryId) {
            $this->selectedHistoryVersionId = (string) $this->editingHistoryId;
        } elseif (!empty($this->historyVersionOptions)) {
            $this->selectedHistoryVersionId = (string) array_key_first($this->historyVersionOptions);
        }
    }

    /**
     * @param array<int, mixed> $ids
     * @return array{0: array<int, string>, 1: int}
     */
    protected function filterExistingIds(array $ids, Builder $query): array
    {
        $normalized = collect($ids)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            return [[], 0];
        }

        $intIds = $normalized
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $existing = (clone $query)
            ->whereIn('id', $intIds)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->values();

        $existingMap = array_fill_keys($existing->all(), true);
        $filtered = $normalized
            ->filter(fn($id) => isset($existingMap[$id]))
            ->values()
            ->all();

        return [$filtered, max(0, $normalized->count() - count($filtered))];
    }

    /**
     * @return array{0: int|null, 1: int}
     */
    protected function resolveHistoryVersioningContext(): array
    {
        if (!$this->editingRootHistoryId) {
            return [null, 1];
        }

        $rootId = (int) $this->editingRootHistoryId;

        $maxVersion = (int) ReportHistory::query()
            ->where('id', $rootId)
            ->orWhere('source_history_id', $rootId)
            ->max('version_no');

        return [$rootId, max(1, $maxVersion + 1)];
    }

    /**
     * @param array<string, mixed> $reportData
     * @return array<string, mixed>
     */
    protected function buildHistoryPayload(array $reportData): array
    {
        return [
            'form_data' => $this->formData,
            'selected_task_ids' => $this->selectedTaskIds,
            'selected_issue_ids' => $this->selectedIssueIds,
            'selected_action_plan_ids' => $this->selectedActionPlanIds,
            'task_order_baseline' => $this->taskOrderBaseline,
            'issue_order_baseline' => $this->issueOrderBaseline,
            'action_plan_order_baseline' => $this->actionPlanOrderBaseline,
            'state' => [
                'task_search' => $this->taskSearch,
                'task_filter_staff_id' => $this->taskFilterStaffId,
                'task_filter_project_id' => $this->taskFilterProjectId,
                'task_filter_status' => $this->taskFilterStatus,
                'show_only_selected_tasks' => $this->showOnlySelectedTasks,
                'task_picker_page' => $this->taskPickerPage,
                'issue_search' => $this->issueSearch,
                'issue_filter_status' => $this->issueFilterStatus,
                'issue_filter_priority' => $this->issueFilterPriority,
                'issue_filter_staff_id' => $this->issueFilterStaffId,
                'show_only_selected_issues' => $this->showOnlySelectedIssues,
                'issue_picker_page' => $this->issuePickerPage,
                'action_plan_search' => $this->actionPlanSearch,
                'action_plan_filter_status' => $this->actionPlanFilterStatus,
                'action_plan_filter_staff_id' => $this->actionPlanFilterStaffId,
                'show_only_selected_action_plans' => $this->showOnlySelectedActionPlans,
                'action_plan_picker_page' => $this->actionPlanPickerPage,
                'active_builder_step' => $this->activeBuilderStep,
                'collapsed_builder_steps' => $this->collapsedBuilderSteps,
                'preview_zoom' => $this->previewZoom,
            ],
            'report_data' => $reportData,
            'editing_from_history_id' => $this->editingHistoryId,
            'editing_root_history_id' => $this->editingRootHistoryId,
            'editing_version_no' => $this->editingVersionNo,
        ];
    }

    protected function currentPreviewFingerprint(): string
    {
        $payload = [
            'formData' => $this->formData,
            'selectedTaskIds' => $this->selectedTaskIds,
            'selectedIssueIds' => $this->selectedIssueIds,
            'selectedActionPlanIds' => $this->selectedActionPlanIds,
        ];

        return sha1((string) json_encode($payload));
    }

    protected function markReportRendered(): void
    {
        $now = $this->uiTimestamp();
        $this->previewSyncedFingerprint = $this->currentPreviewFingerprint();
        $this->previewDirty = false;
        $this->lastPreviewAt = $now;
        $this->lastRenderedAt = $now;
    }

    protected function uiTimestamp(): string
    {
        return now()->timezone(config('app.timezone'))->format('d M Y H:i');
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
            ->withCount('actionPlans')
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
                        ->orWhereHas('actionPlans', fn (Builder $actionPlans) => $actionPlans->where('description', 'like', $term))
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

    protected function buildActionPlanPickerQuery(): Builder
    {
        $selectedIssueIds = $this->getSelectedIssueIdsAsInt();

        return IssueActionPlan::query()
            ->with(['issue.task', 'pic'])
            ->whereIn('issue_id', empty($selectedIssueIds) ? [-1] : $selectedIssueIds)
            ->when($this->showOnlySelectedActionPlans, fn(Builder $query) => $query->whereIn('id', collect($this->selectedActionPlanIds)->map(fn($id) => (int) $id)->all()))
            ->when($this->actionPlanFilterStatus !== '', fn(Builder $query) => $query->where('status', $this->actionPlanFilterStatus))
            ->when($this->actionPlanFilterStaffId, fn(Builder $query) => $query->where('pic_staff_id', $this->actionPlanFilterStaffId))
            ->when($this->actionPlanSearch !== '', function (Builder $query) {
                $term = '%' . $this->actionPlanSearch . '%';

                $query->where(function (Builder $sub) use ($term) {
                    $sub->where('description', 'like', $term)
                        ->orWhereHas('issue', fn(Builder $issue) => $issue->where('issue_name', 'like', $term))
                        ->orWhereHas('issue.task', fn(Builder $task) => $task->where('task_name', 'like', $term))
                        ->orWhereHas('pic', fn(Builder $staff) => $staff->where('name', 'like', $term));
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    protected function resolveActionPlanPickerLastPage(): int
    {
        $total = (clone $this->buildActionPlanPickerQuery())->count();

        return max(1, (int) ceil($total / $this->actionPlanPickerPerPage));
    }

    protected function getActionPlanPickerData(): array
    {
        $query = $this->buildActionPlanPickerQuery();
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $this->actionPlanPickerPerPage));

        $page = max(1, min($this->actionPlanPickerPage, $lastPage));
        $this->actionPlanPickerPage = $page;

        $items = (clone $query)
            ->forPage($page, $this->actionPlanPickerPerPage)
            ->get();

        $from = $total === 0 ? 0 : (($page - 1) * $this->actionPlanPickerPerPage) + 1;
        $to = min($total, $page * $this->actionPlanPickerPerPage);

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

        $selectedIssueIds = $this->getSelectedIssueIdsAsInt();
        $selectedActionPlanIds = collect($this->selectedActionPlanIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $hasActionPlanSelection = !empty($selectedActionPlanIds);

        $issueOrderMap = collect($selectedIssueIds)
            ->values()
            ->flip()
            ->all();
        $actionPlanOrderMap = collect($selectedActionPlanIds)
            ->values()
            ->flip()
            ->all();

        $issuesByTask = Issue::query()
            ->with([
                'staff:id,name',
                'actionPlans' => function ($query) use ($hasActionPlanSelection, $selectedActionPlanIds): void {
                    $query
                        ->with('pic:id,name')
                        ->when(
                            $hasActionPlanSelection,
                            fn ($subQuery) => $subQuery->whereIn('id', $selectedActionPlanIds)
                        )
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->whereIn('id', empty($selectedIssueIds) ? [-1] : $selectedIssueIds)
            ->get(['id', 'task_id', 'staff_id', 'issue_name', 'description', 'status'])
            ->sortBy(fn(Issue $issue) => $issueOrderMap[(int) $issue->id] ?? PHP_INT_MAX)
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
            ->map(function (Task $task, int $index) use ($issuesByTask, $actionPlanOrderMap, $hasActionPlanSelection) {
                $selectedIssues = $issuesByTask->get($task->id, collect());
                $taskStatus = $this->normalizeReportStatus((string) ($task->status ?? ''));
                $projectName = trim((string) ($task->project?->project_name ?? ''));
                $taskName = trim((string) ($task->task_name ?? '-'));
                $projectTaskText = $projectName !== ''
                    ? ($projectName . "\n" . $taskName)
                    : $taskName;
                $taskPercent = $this->formatTaskPercent($task->progress);

                $issueEntries = $selectedIssues
                    ->flatMap(fn (Issue $issue) => $this->buildIssueEntries($issue, $actionPlanOrderMap, $hasActionPlanSelection))
                    ->filter()
                    ->values()
                    ->all();

                $taskEvaluasi = $this->reportStatusLabel($taskStatus);

                return [
                    'no' => $index + 1,
                    'project_task' => $projectTaskText !== '' ? $projectTaskText : '-',
                    'project_name' => $projectName !== '' ? $projectName : '-',
                    'task_name' => $taskName !== '' ? $taskName : '-',
                    'percent' => $taskPercent,
                    'issue_entries' => $issueEntries,
                    'task_status_key' => $taskStatus,
                    'task_evaluasi' => $taskEvaluasi,
                    // Backward compatibility for existing templates/logic.
                    'evaluasi' => $taskEvaluasi,
                    'item' => (string) ($task->task_name ?? '-'),
                    'input' => (string) ($task->input ?? '-'),
                    'output' => (string) ($task->output ?? '-'),
                    'target' => $task->tanggal ? $this->formatIndonesianDate($task->tanggal) : '-',
                    'pic' => (string) ($task->staff?->name ?? '-'),
                ];
            })
            ->all();
    }

    protected function formatTaskPercent(mixed $progress): string
    {
        if (!is_numeric($progress)) {
            return '-';
        }

        $value = max(0, min(100, (float) $progress));

        if ((float) ((int) $value) === $value) {
            return ((string) ((int) $value)) . '%';
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%';
    }

    /**
     * @return array{
     *   label:string,
     *   primary_key:string,
     *   items:array<int, array{key:string,label:string}>
     * }
     */
    protected function summarizeIssueStatuses(Collection $issueEntries, string $taskOutput = ''): array
    {
        if ($issueEntries->isEmpty()) {
            return ['label' => '-', 'primary_key' => 'tbd', 'items' => []];
        }

        $taskLineEstimate = $this->estimateWrappedLines($taskOutput, 62);

        $statusItems = $issueEntries
            ->map(function (array $issueEntry): array {
                $key = $this->normalizeReportStatus((string) ($issueEntry['status_key'] ?? ''));
                $desc = (string) ($issueEntry['action_plan'] ?? '');
                $line = $desc === '' ? '-' : "- {$desc}";

                return [
                    'key' => $key,
                    'label' => $this->reportStatusLabel($key),
                    'line_estimate' => $this->estimateWrappedLines($line, 62),
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

        $itemsWithSpacer = $statusItems
            ->values()
            ->map(function (array $item, int $index) use ($statusItems, $taskLineEstimate): array {
                $spacerLines = $index === 0
                    ? max(1, $taskLineEstimate + 1)
                    : max(1, (int) ($statusItems[$index - 1]['line_estimate'] ?? 1));

                return [
                    'key' => (string) ($item['key'] ?? 'tbd'),
                    'label' => (string) ($item['label'] ?? 'TBD'),
                    'spacer' => max(8, min(260, $spacerLines * 15)),
                ];
            })
            ->all();

        return [
            'label' => $labels->implode(', '),
            'primary_key' => $this->pickPrimaryStatusKey($normalizedStatuses),
            'items' => $itemsWithSpacer,
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

    protected function estimateWrappedLines(string $text, int $charsPerLine = 62): int
    {
        $plain = trim((string) preg_replace('/\s+/', ' ', $text));

        if ($plain === '') {
            return 1;
        }

        return max(1, (int) ceil(mb_strlen($plain) / max(1, $charsPerLine)));
    }

    /**
     * @return array<int, array{
     *   issue_id:int,
     *   action_plan_id:int,
     *   issue:string,
     *   action_plan:string,
     *   status_key:string,
     *   status_label:string,
     *   pic:string,
     *   has_action_plan:bool
     * }>
     */
    protected function buildIssueEntries(
        Issue $issue,
        array $actionPlanOrderMap = [],
        bool $restrictToSelectedActionPlans = false,
    ): array
    {
        $issueText = $this->normalizeIssueText((string) ($issue->issue_name ?? ''));
        $issueText = $issueText !== '' ? $issueText : '-';

        // Step 4 belum memilih action plan: tampilkan issue dulu tanpa detail action plan.
        if (!$restrictToSelectedActionPlans) {
            $issueStatus = $this->normalizeReportStatus((string) ($issue->status ?? 'opened'));

            return [[
                'issue_id' => (int) $issue->id,
                'action_plan_id' => 0,
                'issue' => $issueText,
                'action_plan' => '-',
                'status_key' => $issueStatus,
                'status_label' => $this->reportStatusLabel($issueStatus),
                'pic' => (string) ($issue->staff?->name ?? '-'),
                'has_action_plan' => false,
            ]];
        }

        $actionPlans = $issue->actionPlans;

        if (!empty($actionPlanOrderMap)) {
            $actionPlans = $actionPlans
                ->sortBy(fn($plan) => $actionPlanOrderMap[(int) ($plan->id ?? 0)] ?? PHP_INT_MAX)
                ->values();
        }

        $entries = $actionPlans
            ->map(function ($plan) use ($issue, $issueText): ?array {
                $description = $this->normalizeIssueText((string) ($plan->description ?? ''));
                $description = $description !== '' ? $description : '-';

                $statusKey = $this->normalizeReportStatus((string) ($plan->status ?? 'opened'));
                $picName = (string) ($plan->pic?->name ?? $issue->staff?->name ?? '-');

                return [
                    'issue_id' => (int) $issue->id,
                    'action_plan_id' => (int) ($plan->id ?? 0),
                    'issue' => $issueText,
                    'action_plan' => $description,
                    'status_key' => $statusKey,
                    'status_label' => $this->reportStatusLabel($statusKey),
                    'pic' => $picName,
                    'has_action_plan' => true,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (!empty($entries)) {
            return $entries;
        }

        if ($restrictToSelectedActionPlans) {
            return [];
        }

        $legacyPlans = $this->parseLegacyIssueActionPlans($issue->description);

        if (!empty($legacyPlans)) {
            return collect($legacyPlans)
                ->map(function (array $plan) use ($issue, $issueText): ?array {
                    $description = $this->normalizeIssueText((string) ($plan['description'] ?? ''));
                    $description = $description !== '' ? $description : '-';

                    $statusKey = $this->normalizeReportStatus((string) ($plan['status'] ?? $issue->status ?? 'opened'));
                    $picId = is_numeric($plan['pic_staff_id'] ?? null) ? (int) $plan['pic_staff_id'] : null;
                    $picName = $this->resolveStaffNameById($picId) ?? (string) ($issue->staff?->name ?? '-');

                    return [
                        'issue_id' => (int) $issue->id,
                        'action_plan_id' => 0,
                        'issue' => $issueText,
                        'action_plan' => $description,
                        'status_key' => $statusKey,
                        'status_label' => $this->reportStatusLabel($statusKey),
                        'pic' => $picName,
                        'has_action_plan' => true,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        $legacyDescription = $this->normalizeIssueText((string) ($issue->description ?? ''));
        $legacyDescription = $legacyDescription !== '' ? $legacyDescription : '-';

        $issueStatus = $this->normalizeReportStatus((string) ($issue->status ?? 'opened'));

        return [[
            'issue_id' => (int) $issue->id,
            'action_plan_id' => 0,
            'issue' => $issueText,
            'action_plan' => $legacyDescription,
            'status_key' => $issueStatus,
            'status_label' => $this->reportStatusLabel($issueStatus),
            'pic' => (string) ($issue->staff?->name ?? '-'),
            'has_action_plan' => false,
        ]];
    }

    /**
     * @return array<int, array{description:string,status:string,pic_staff_id:int|null}>
     */
    protected function parseLegacyIssueActionPlans(mixed $raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(function (mixed $item): ?array {
                if (!is_array($item)) {
                    return null;
                }

                $description = (string) ($item['description'] ?? '');
                $status = (string) ($item['status'] ?? 'opened');
                $picRaw = $item['pic_staff_id'] ?? $item['pic'] ?? null;
                $pic = is_numeric($picRaw) ? (int) $picRaw : null;

                if (trim(strip_tags($description)) === '') {
                    return null;
                }

                return [
                    'description' => $description,
                    'status' => $status !== '' ? $status : 'opened',
                    'pic_staff_id' => $pic,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeIssueText(string $raw): string
    {
        $text = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = trim(strip_tags($text));

        return (string) (preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    protected function resolveStaffNameById(?int $staffId): ?string
    {
        if (!$staffId) {
            return null;
        }

        static $staffNameCache = [];

        if (array_key_exists($staffId, $staffNameCache)) {
            return $staffNameCache[$staffId];
        }

        $staffNameCache[$staffId] = Staff::query()
            ->whereKey($staffId)
            ->value('name');

        return $staffNameCache[$staffId];
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

    /** @return array<int> */
    protected function getSelectedIssueIdsAsInt(): array
    {
        return collect($this->selectedIssueIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function getSelectedTasksOrdered(): Collection
    {
        $taskIds = $this->getSelectedTaskIdsAsInt();
        if (empty($taskIds)) {
            return collect();
        }

        $orderMap = collect($taskIds)->values()->flip()->all();

        return Task::query()
            ->with(['staff', 'project'])
            ->whereIn('id', $taskIds)
            ->get()
            ->sortBy(fn(Task $task) => $orderMap[(int) $task->id] ?? PHP_INT_MAX)
            ->values();
    }

    protected function getSelectedIssuesOrdered(): Collection
    {
        $issueIds = $this->getSelectedIssueIdsAsInt();

        if (empty($issueIds)) {
            return collect();
        }

        $orderMap = collect($issueIds)->values()->flip()->all();

        return Issue::query()
            ->with(['task', 'staff'])
            ->whereIn('id', $issueIds)
            ->get()
            ->sortBy(fn(Issue $issue) => $orderMap[(int) $issue->id] ?? PHP_INT_MAX)
            ->values();
    }

    protected function getSelectedActionPlansOrdered(): Collection
    {
        $actionPlanIds = collect($this->selectedActionPlanIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($actionPlanIds)) {
            return collect();
        }

        $orderMap = collect($actionPlanIds)->values()->flip()->all();

        return IssueActionPlan::query()
            ->with(['issue.task', 'pic'])
            ->whereIn('id', $actionPlanIds)
            ->get()
            ->sortBy(fn(IssueActionPlan $actionPlan) => $orderMap[(int) $actionPlan->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * @param  array<int, string|int>  $ids
     * @return array<int, string>
     */
    protected function moveIdPosition(array $ids, string $targetId, int $direction): array
    {
        $normalized = collect($ids)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->values()
            ->all();

        $index = array_search($targetId, $normalized, true);
        if ($index === false) {
            return $normalized;
        }

        $nextIndex = $index + ($direction < 0 ? -1 : 1);
        if ($nextIndex < 0 || $nextIndex >= count($normalized)) {
            return $normalized;
        }

        [$normalized[$index], $normalized[$nextIndex]] = [$normalized[$nextIndex], $normalized[$index]];

        return array_values($normalized);
    }

    /**
     * @param  array<int, string|int>  $currentIds
     * @param  array<int, string|int>  $orderedIds
     * @return array<int, string>
     */
    protected function applyExplicitOrder(array $currentIds, array $orderedIds): array
    {
        $current = collect($currentIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '')
            ->unique()
            ->values();

        if ($current->isEmpty()) {
            return [];
        }

        $allowed = array_fill_keys($current->all(), true);

        $ordered = collect($orderedIds)
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => $id !== '' && isset($allowed[$id]))
            ->unique()
            ->values();

        $missing = $current
            ->reject(fn($id) => $ordered->contains($id))
            ->values();

        return $ordered
            ->concat($missing)
            ->values()
            ->all();
    }

    /**
     * @return array{
     *   no:float,
     *   project_task:float,
     *   percent:float,
     *   issue:float,
     *   action_plan:float,
     *   pic:float,
     *   evaluasi:float
     * }
     */
    protected function defaultColumnWidths(): array
    {
        return [
            'no' => 4.0,
            'project_task' => 14.0,
            'percent' => 16.0,
            'issue' => 36.0,
            'action_plan' => 10.0,
            'pic' => 10.0,
            'evaluasi' => 10.0,
        ];
    }

    /**
     * @return array{
     *   no:float,
     *   project_task:float,
     *   percent:float,
     *   issue:float,
     *   action_plan:float,
     *   pic:float,
     *   evaluasi:float
     * }
     */
    protected function loadSavedColumnWidths(): array
    {
        $userId = auth()->id();

        if (!$userId) {
            return $this->defaultColumnWidths();
        }

        $preference = TaskReportBuilderPreference::query()
            ->where('user_id', $userId)
            ->first();

        if (!$preference || !is_array($preference->column_widths)) {
            return $this->defaultColumnWidths();
        }

        return $this->resolveReportColumnWidths($preference->column_widths);
    }

    /**
     * @param array<string, mixed> $widths
     */
    protected function setPersistedColumnWidthFingerprint(array $widths): void
    {
        $normalized = $this->resolveReportColumnWidths($widths);
        $this->lastPersistedColumnWidthFingerprint = sha1((string) json_encode($normalized));
    }

    protected function persistColumnWidths(): void
    {
        $userId = auth()->id();

        if (!$userId) {
            return;
        }

        $normalized = $this->resolveReportColumnWidths(data_get($this->formData, 'column_widths', []));
        $fingerprint = sha1((string) json_encode($normalized));

        if ($fingerprint === $this->lastPersistedColumnWidthFingerprint) {
            return;
        }

        TaskReportBuilderPreference::query()->updateOrCreate(
            ['user_id' => $userId],
            ['column_widths' => $normalized]
        );

        $this->lastPersistedColumnWidthFingerprint = $fingerprint;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *   no:float,
     *   project_task:float,
     *   percent:float,
     *   issue:float,
     *   action_plan:float,
     *   pic:float,
     *   evaluasi:float
     * }
     */
    protected function resolveReportColumnWidths(array $raw): array
    {
        $defaults = $this->defaultColumnWidths();

        $normalized = [];

        foreach ($defaults as $key => $defaultValue) {
            $value = data_get($raw, $key, $defaultValue);
            $value = is_numeric($value) ? (float) $value : $defaultValue;
            $normalized[$key] = max(1.0, min(100.0, $value));
        }

        $sum = array_sum($normalized);

        if ($sum <= 0) {
            return $defaults;
        }

        $scale = 100 / $sum;
        foreach ($normalized as $key => $value) {
            $normalized[$key] = round($value * $scale, 2);
        }

        $sumWithoutLast = round(
            $normalized['no']
            + $normalized['project_task']
            + $normalized['percent']
            + $normalized['issue']
            + $normalized['action_plan']
            + $normalized['pic'],
            2
        );
        $normalized['evaluasi'] = round(max(1.0, 100 - $sumWithoutLast), 2);

        return $normalized;
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
