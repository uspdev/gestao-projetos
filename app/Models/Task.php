<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'label' => 'string',
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'priority',
        'status',
        'label',
        'start_date',
        'due_date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                    ->withTimestamps();
    }
}
