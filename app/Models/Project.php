<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use app\Models\Task;

class Project extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    protected $fillable = [
        'name',
        'status',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                    ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // public function statusUpdates(): HasMany
    // {
    //     return $this->hasMany(StatusUpdate::class);
    // }
}
