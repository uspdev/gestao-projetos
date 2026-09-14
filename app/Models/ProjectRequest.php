<?php

namespace App\Models;

use App\Enums\ProjectRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_system_id',
        'title',
        'description',
        'source_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectRequestStatus::class,
            'evaluated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function clientSystem(): BelongsTo
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
