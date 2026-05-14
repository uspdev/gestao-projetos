<?php

namespace App\Models;

use App\Enums\Meeting\MeetingStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'title',
        'scheduled_at',
        'location',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => MeetingStatus::class,
        ];
    }

    /**
     * Relacionamento com projetos N-N
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'meeting_projects');
    }

    /**
     * Relacionamento com comentarios (morph)
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
