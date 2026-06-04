@php
    $activeStatuses = collect(data_get($this->tableFilters ?? [], 'status.values', []))
        ->filter()
        ->values()
        ->all();

    $isActive = fn (string $status): bool => in_array($status, $activeStatuses, true);
    $isAll = empty($activeStatuses);
@endphp

<style>
    /* 1. OUTER WRAPPER */
    .custom-tab-wrapper { background-color: #f1f5f9; border: 1px solid #e2e8f0; }
    .dark .custom-tab-wrapper { background-color: #111827; border: 1px solid transparent; } /* Sangat gelap, tanpa border */

    /* 2. TOMBOL AKTIF */
    .custom-tab-active {
        background-color: #ffffff; color: #0f172a; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
    }
    .dark .custom-tab-active {
        background-color: #1f2937 !important; /* Abu-abu gelap, bukan putih! */
        color: #f8fafc !important; /* Teks putih */
        border: 1px solid transparent !important; /* Buang garis tepi */
        box-shadow: none !important;
    }

    /* 3. TOMBOL TIDAK AKTIF */
    .custom-tab-inactive { background-color: transparent; color: #64748b; border: 1px solid transparent; }
    .dark .custom-tab-inactive { color: #94a3b8; }
    .custom-tab-inactive:hover { color: #0f172a; }
    .dark .custom-tab-inactive:hover { color: #f8fafc; }

    /* 4. BADGE (Paksa transparan tanpa border di Dark Mode) */
    .custom-badge { border: 1px solid; }
    .dark .custom-badge { border: none !important; }
    
    /* Warna Badge Spesifik untuk Dark Mode (Soft / Transparan) */
    .dark .badge-all { background-color: rgba(249, 115, 22, 0.1) !important; color: #fb923c !important; }
    .dark .badge-open { background-color: rgba(59, 130, 246, 0.1) !important; color: #60a5fa !important; }
    .dark .badge-progress { background-color: rgba(16, 185, 129, 0.1) !important; color: #34d399 !important; }
    .dark .badge-closed { background-color: rgba(244, 63, 94, 0.1) !important; color: #fb7185 !important; }
    .dark .badge-overdue { background-color: rgba(245, 158, 11, 0.1) !important; color: #fbbf24 !important; }
    .dark .badge-postponed { background-color: rgba(71, 85, 105, 0.3) !important; color: #cbd5e1 !important; }
</style>

<div class="w-full mb-6">
    <div class="inline-flex items-center gap-1 p-1 rounded-xl custom-tab-wrapper">
        
        <button type="button" wire:click="setQuickStatusFilter('all')" class="flex items-center gap-1.5 px-3 py-1 text-sm font-medium rounded-lg transition-all {{ $isAll ? 'custom-tab-active' : 'custom-tab-inactive' }}">
            All
        </button>

        <button type="button" wire:click="setQuickStatusFilter('opened')" class="flex items-center gap-1.5 px-3 py-1 text-sm font-medium rounded-lg transition-all {{ $isActive('opened') ? 'custom-tab-active' : 'custom-tab-inactive' }}">
            Opened
        </button>

        <button type="button" wire:click="setQuickStatusFilter('progress')" class="flex items-center gap-1.5 px-3 py-1 text-sm font-medium rounded-lg transition-all {{ $isActive('progress') ? 'custom-tab-active' : 'custom-tab-inactive' }}">
            Progress
        </button>

        <button type="button" wire:click="setQuickStatusFilter('closed')" class="flex items-center gap-1.5 px-3 py-1 text-sm font-medium rounded-lg transition-all {{ $isActive('closed') ? 'custom-tab-active' : 'custom-tab-inactive' }}">
            Canceled
        </button>

        <button type="button" wire:click="setQuickStatusFilter('overdue')" class="flex items-center gap-1.5 px-3 py-1 text-sm font-medium rounded-lg transition-all {{ $isActive('overdue') ? 'custom-tab-active' : 'custom-tab-inactive' }}">
            Overdue
            </span>
        </button>

        <button type="button" wire:click="setQuickStatusFilter('postponed')" class="flex items-center gap-1.5 px-3 py-1 text-sm font-medium rounded-lg transition-all {{ $isActive('postponed') ? 'custom-tab-active' : 'custom-tab-inactive' }}">
            Postponed
        </button>

    </div>
</div>