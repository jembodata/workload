<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    protected $fillable = [
        'task_name',
        'staff_id',
        'tanggal',
        'tanggal_akhir',
        'estimasi_jam',
        'status',
        'input',
        'is_long_term',
        'allocation_hours',
        'priority',
        'output',
        'total_overdue',
        'progress',
    ];

    protected static function booted()
    {
        static::saving(function ($task) {
            $today = \Carbon\Carbon::today();

            // Kalau status closed → progress otomatis 100
            if ($task->status === 'closed') {
                $task->progress = 100;
                return;
            }

            // Kalau status postponed → overdue = 0
            if ($task->status === 'postponed') {
                $task->total_overdue = 0;
                return;
            }

            // Hitung overdue untuk long term / non long term
            if (!$task->is_long_term && $task->tanggal) {
                $target = \Carbon\Carbon::parse($task->tanggal);
                $task->total_overdue = $today->greaterThan($target)
                    ? $today->diffInDays($target)
                    : 0;
            } elseif ($task->is_long_term && $task->tanggal_akhir) {
                $end = \Carbon\Carbon::parse($task->tanggal_akhir);
                $task->total_overdue = $today->greaterThan($end)
                    ? $today->diffInDays($end)
                    : 0;
            } else {
                $task->total_overdue = 0;
            }
        });
    }
}
