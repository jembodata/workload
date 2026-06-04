# Theme Foundation v1

Baseline UI consistency untuk panel admin (Filament native-only) dengan arah visual **Clean Industrial Amber**.

## Tokens
- Gunakan token di `resources/css/filament/admin/theme.css` untuk:
  - `--tf-primary-*`
  - `--tf-success-500`, `--tf-warning-500`, `--tf-danger-500`, `--tf-muted-500`
  - `--tf-bg-*`, `--tf-border*`, `--tf-text-*`
  - `--tf-radius-*`, `--tf-shadow-*`

## Do / Don’t
- Do:
  - Pakai class foundation (`rb-*`, `status-pill--*`, komponen Filament standar).
  - Tambah varian visual lewat token, bukan hardcode warna berulang.
  - Scope style halaman khusus dengan class root page (contoh: `.task-report-builder-page`).
- Don’t:
  - Jangan edit file `vendor/*`.
  - Jangan taruh CSS besar dalam `<style>` inline per-page.
  - Jangan tambahkan warna status baru tanpa mapping global.

## Komponen wajib konsisten
- Navigation/topbar
- Buttons (primary/secondary/danger/disabled/loading)
- Form controls (input/select/textarea/date)
- Card/panel sections
- Table header/row/hover/selected
- Modal/slideover header-body-footer
- Badge/status pills

## Status mapping standar
- `opened` -> biru
- `progress` -> kuning
- `closed` -> hijau
- `overdue` -> merah
- `postponed` -> abu

## QA Visual Checklist (sebelum merge)
1. Spacing antar section konsisten (desktop).
2. Heading/body/helper hierarchy jelas.
3. Hover/focus/disabled/loading state terlihat.
4. Tabel tidak overflow/overlap.
5. Modal/slideover action alignment rapi.
6. Report Builder end-to-end tetap jalan:
   - pilih task/issue/action plan
   - reorder
   - preview
   - render PDF
