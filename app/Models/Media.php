<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    private const FILE_OWNERS = [
        Project::class,
        Task::class,
        Meeting::class,
    ];

    private const IMMUTABLE_ATTRIBUTES = [
        'uuid',
        'model_type',
        'model_id',
        'original_name',
        'uploaded_by',
        'file_name',
        'disk',
        'conversions_disk',
        'mime_type',
        'size',
    ];

    protected $fillable = [
        'display_name',
        'original_name',
        'uploaded_by',
    ];

    protected $appends = [
        'display_name',
        'uuid_name',
    ];

    protected $hidden = [
        'name',
        'file_name',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $media): void {
            $media->ensureSupportedOwner();
            $media->original_name ??= $media->display_name;

            $fileUuid = pathinfo($media->file_name, PATHINFO_FILENAME);

            if (! Str::isUuid($fileUuid)) {
                throw new LogicException('O nome físico do Arquivo deve usar UUID.');
            }

            $media->uuid = $fileUuid;
            $media->setCustomProperty('thumbnail_status', 'pending');
        });

        static::updating(function (self $media): void {
            foreach (self::IMMUTABLE_ATTRIBUTES as $attribute) {
                if ($media->isDirty($attribute)) {
                    throw new LogicException("O atributo {$attribute} do Arquivo é imutável.");
                }
            }
        });

        static::deleted(function (self $media): void {
            if (Schema::hasTable('mentions')) {
                $media->incomingMentions()->delete();
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sharedWithMeetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_file_shares')
            ->withPivot('shared_by')
            ->withTimestamps();
    }

    public function incomingMentions(): HasMany
    {
        return $this->hasMany(Mention::class, 'target_id')
            ->where('target_type', 'file');
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?string => $attributes['name'] ?? null,
            set: fn (?string $value): array => ['name' => $value],
        );
    }

    protected function uuidName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?string => $attributes['file_name'] ?? null,
            set: fn (): never => throw new LogicException('O Nome UUID do Arquivo é imutável.'),
        );
    }

    private function ensureSupportedOwner(): void
    {
        $ownerClass = Relation::getMorphedModel($this->model_type) ?? $this->model_type;

        if (! in_array($ownerClass, self::FILE_OWNERS, true)) {
            throw new LogicException('Apenas Projeto, Tarefa e Reunião podem ser Proprietários de Arquivo.');
        }
    }
}
