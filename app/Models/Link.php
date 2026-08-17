<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use LogicException;

// Classe similar a Media, mas sem o uso da biblioteca Spatie Media Library
class Link extends Model
{
    private const LINK_OWNERS = [
        Project::class,
        Task::class,
        Meeting::class,
    ];

    private const IMMUTABLE_ATTRIBUTES = [
        'uuid',
        'linkable_type',
        'linkable_id',
        'created_by',
    ];

    protected $fillable = [
        'name',
        'url',
        'created_by',
    ];

    protected $appends = ['display_name'];

    /**
     * Inicializa os eventos do model.
     *
     * Durante a criação, valida se o proprietário do link é suportado
     * e gera automaticamente um UUID caso ele ainda não tenha sido definido.
     *
     * Durante a atualização, impede alterações nos atributos definidos
     * em {@see self::IMMUTABLE_ATTRIBUTES}, garantindo sua imutabilidade
     * após a criação do registro.
     *
     * @throws LogicException Caso um atributo imutável seja alterado.
     */
    protected static function booted(): void
    {
        static::creating(function (self $link): void {
            $link->ensureSupportedOwner();
            $link->uuid ??= (string) Str::uuid();
        });

        static::updating(function (self $link): void {
            foreach (self::IMMUTABLE_ATTRIBUTES as $attribute) {
                if ($link->isDirty($attribute)) {
                    throw new LogicException("O atributo {$attribute} do Link é imutável.");
                }
            }
        });
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sharedWithMeetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_link_shares')
            ->withPivot('shared_by')
            ->withTimestamps();
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes): ?string => $attributes['name'] ?? null,
            set: fn(?string $value): array => ['name' => $value],
        );
    }

    private function ensureSupportedOwner(): void
    {
        $owner = Relation::getMorphedModel($this->linkable_type) ?? $this->linkable_type;

        if (! in_array($owner, self::LINK_OWNERS, true)) {
            throw new LogicException('Apenas Projeto, Tarefa e Reunião podem ser Proprietários de Link.');
        }
    }
}
