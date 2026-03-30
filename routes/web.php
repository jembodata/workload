<?php

use App\Models\ReportHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});

Route::get('/admin/task-report/preview/{token}', function (string $token) {
    $data = Cache::get("task-report-pdf:{$token}");
    abort_if(!$data, 404);

    $orientation = in_array(($data['orientation'] ?? 'portrait'), ['portrait', 'landscape'], true)
        ? $data['orientation']
        : 'portrait';

    // First pass: render once to get actual page count from DomPDF canvas.
    $probe = Pdf::loadView('filament.pages.task-report-builder-pdf', $data)
        ->setPaper('a4', $orientation);
    $dompdf = $probe->getDomPDF();
    $dompdf->render();
    $totalPages = max(1, (int) $dompdf->getCanvas()->get_page_count());

    // Second pass: inject dynamic page label based on actual rendered pages.
    $data['pageLabel'] = "1 dari {$totalPages}";
    $pdf = Pdf::loadView('filament.pages.task-report-builder-pdf', $data)
        ->setPaper('a4', $orientation);

    $fileName = 'minutes-meeting-' . now()->format('Ymd-His') . '.pdf';

    return response($pdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"{$fileName}\"",
    ]);
})->middleware(['web', 'auth'])->name('task-report.preview');

Route::get('/admin/task-report/history/{history}/pdf', function (ReportHistory $history) {
    abort_unless((int) auth()->id() === (int) $history->printed_by, 403);
    abort_if(blank($history->pdf_path), 404);
    abort_unless(Storage::disk('local')->exists($history->pdf_path), 404);

    $fullPath = Storage::disk('local')->path($history->pdf_path);
    $fileName = basename($history->pdf_path);

    return response()->file($fullPath, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"{$fileName}\"",
    ]);
})->middleware(['web', 'auth'])->name('task-report.history.pdf');

Route::get('/admin/task-report/history/{history}/docx', function (ReportHistory $history) {
    abort_unless((int) auth()->id() === (int) $history->printed_by, 403);
    abort_if(blank($history->docx_path), 404);
    abort_unless(Storage::disk('local')->exists($history->docx_path), 404);

    $fullPath = Storage::disk('local')->path($history->docx_path);
    $fileName = basename($history->docx_path);

    return response()->file($fullPath, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'Content-Disposition' => "inline; filename=\"{$fileName}\"",
    ]);
})->middleware(['web', 'auth'])->name('task-report.history.docx');
