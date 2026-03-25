<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

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
