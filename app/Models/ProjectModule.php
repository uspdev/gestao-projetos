<?php

namespace App\Models;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'module_id',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }
    // =========== Relacionamentos =============

    // Um projeto pode ter várias configurações de módulos
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Um módulo pode ter várias configurações por projeto
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
