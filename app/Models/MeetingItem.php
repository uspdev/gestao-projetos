<?php

namespace App\Models;

use App\Morphs\DiscussableMap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MeetingItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'meeting_id',
        'discussable_type',
        'discussable_id',
        'title',
        'order',
        'notes',
    ];

    protected $touches = ['meeting', 'discussable'];

    protected static function booted(): void
    {
        static::saving(function (MeetingItem $meetingItem) {
            $meetingItem->normalizeTitle();
            $meetingItem->assertRepresentationIsValid();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('meeting_item')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function discussable(): MorphTo
    {
        return $this->morphTo();
    }

    private function normalizeTitle(): void
    {
        if ($this->title === null) {
            return;
        }

        $title = trim((string) $this->title);
        $this->title = $title === '' ? null : $title;
    }

    private function assertRepresentationIsValid(): void
    {
        $hasDiscussableType = filled($this->discussable_type);
        $hasDiscussableId = filled($this->discussable_id);
        $hasTitle = $this->title !== null;

        if ($hasDiscussableType xor $hasDiscussableId) {
            throw new InvalidArgumentException(
                'O item de pauta deve ter o tipo e o identificador do projeto ou tarefa.'
            );
        }

        if ($hasDiscussableType && !DiscussableMap::resolveClass((string) $this->discussable_type)) {
            throw new InvalidArgumentException(
                'O tipo do item de pauta não representa um projeto ou tarefa válido.'
            );
        }

        if ($hasTitle && ($hasDiscussableType || $hasDiscussableId)) {
            throw new InvalidArgumentException(
                'O item de pauta não pode ter vínculo e título independente ao mesmo tempo.'
            );
        }

        if (!$hasTitle && !$hasDiscussableType) {
            throw new InvalidArgumentException(
                'O item de pauta deve ter um projeto ou tarefa vinculada ou um título independente.'
            );
        }

        if ($hasTitle && mb_strlen($this->title) < 3) {
            throw new InvalidArgumentException(
                'O título do item de pauta deve ter pelo menos 3 caracteres.'
            );
        }

        if ($hasTitle && mb_strlen($this->title) > 255) {
            throw new InvalidArgumentException(
                'O título do item de pauta não pode exceder 255 caracteres.'
            );
        }
    }
}
