<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'source_history_id',
        'version_no',
        'payload',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'version_no' => 'integer',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function sourceHistory(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_history_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'source_history_id');
    }
}
