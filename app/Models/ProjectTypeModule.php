<?php

namespace App\Models;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTypeModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_type_id',
        'module_id',
        'enabled',
        'required',
        'editable',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'required' => 'boolean',
            'editable' => 'boolean',
            'config' => 'array',
        ];
    }
    // =========== Relacionamentos =============

    // Um tipo de projeto pode ter várias configurações de módulos
    public function projectType(): BelongsTo
    {
        return $this->belongsTo('App\\Models\\ProjectType');
    }

    // Um módulo pode ter várias configurações por tipo de projeto
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
