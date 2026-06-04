<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_histories', function (Blueprint $table) {
            $table->foreignId('source_history_id')
                ->nullable()
                ->after('printed_at')
                ->constrained('report_histories')
                ->nullOnDelete();

            $table->unsignedInteger('version_no')
                ->default(1)
                ->after('source_history_id');

            $table->index(['source_history_id', 'version_no'], 'report_histories_source_version_idx');
        });

        DB::table('report_histories')
            ->whereNull('version_no')
            ->update(['version_no' => 1]);
    }

    public function down(): void
    {
        Schema::table('report_histories', function (Blueprint $table) {
            $table->dropIndex('report_histories_source_version_idx');
            $table->dropConstrainedForeignId('source_history_id');
            $table->dropColumn('version_no');
        });
    }
};
