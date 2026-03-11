<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Task;
use App\Models\Role;

class Staff extends Model
{
    use HasFactory;

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'staff_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    protected $fillable = [
        'name',
        'role_id',
        'color',
    ];
}
