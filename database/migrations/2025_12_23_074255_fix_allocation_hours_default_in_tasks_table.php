<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        $today = Carbon::today();

        // Update semua record lama
        DB::table('tasks')->get()->each(function ($task) use ($today) {
            $overdue = 0;

            if (in_array($task->status, ['closed', 'done', 'postponed'], true)) {
                $overdue = 0;
            } elseif (!$task->is_long_term && $task->tanggal) {
                $target = Carbon::parse($task->tanggal);
                $overdue = $today->greaterThan($target)
                    ? $today->diffInDays($target)
                    : 0;
            } elseif ($task->is_long_term && $task->tanggal_akhir) {
                $end = Carbon::parse($task->tanggal_akhir);
                $overdue = $today->greaterThan($end)
                    ? $today->diffInDays($end)
                    : 0;
            }

            DB::table('tasks')
                ->where('id', $task->id)
                ->update(['total_overdue' => $overdue]);
        });
    }

    public function down(): void
    {
        // Rollback: kembalikan semua ke 0
        DB::table('tasks')->update(['total_overdue' => 0]);
    }
};
