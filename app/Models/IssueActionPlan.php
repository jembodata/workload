<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueActionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'description',
        'status',
        'pic_staff_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'pic_staff_id');
    }
}

