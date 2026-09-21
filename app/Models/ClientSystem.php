<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Uspdev\ApiKeys\Traits\HasApiAbilities;
use Uspdev\ApiKeys\Traits\HasApiKeys;

class ClientSystem extends Model
{
    use Auditable, HasApiAbilities, HasApiKeys, HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return list<string>
     */
    public function abilities(string $role): array
    {
        return match ($role) {
            'viewer' => [
                'projects.read',
                'tasks.read',
            ],
            'contributor' => [
                'projects.read',
                'tasks.read',
            ],
            default => [],
        };
    }
}
