<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportHistory extends Model
{
    protected $fillable = [
        'title_id',
        'title_en',
        'document_no',
        'revision',
        'orientation',
        'page_label',
        'pdf_path',
        'docx_path',
        'printed_by',
        'printed_at',
        'payload',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
