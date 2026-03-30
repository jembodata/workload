<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_histories', function (Blueprint $table) {
            $table->id();
            $table->string('title_id')->nullable();
            $table->string('title_en')->nullable();
            $table->string('document_no')->nullable();
            $table->string('revision')->nullable();
            $table->string('orientation', 20)->default('portrait');
            $table->string('page_label')->nullable();
            $table->string('pdf_path');
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_histories');
    }
};

