<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskReportBuilderPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'column_widths',
    ];

    protected $casts = [
        'column_widths' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

