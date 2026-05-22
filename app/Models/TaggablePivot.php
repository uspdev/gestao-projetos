<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaggablePivot extends MorphPivot
{
    protected static function booted(): void
    {
        static::created(function (TaggablePivot $pivot) {
            $pivot->logTagEvent('tag_attached');
        });

        static::deleted(function (TaggablePivot $pivot) {
            $pivot->logTagEvent('tag_detached');
        });
    }

    private function logTagEvent(string $event): void
    {
        $taggable = $this->resolveTaggable();

        if (! $taggable) {
            return;
        }

        activity()
            ->useLog('tag')
            ->event($event)
            ->performedOn($taggable)
            ->withProperties([
                'attributes' => [
                    'tag_id' => $this->getAttribute('tag_id'),
                ],
            ])
            ->log($event);
    }

    private function resolveTaggable(): ?Model
    {
        $type = $this->getAttribute('taggable_type');
        $id = $this->getAttribute('taggable_id');

        if (! $type || ! $id) {
            return null;
        }

        $class = Relation::getMorphedModel($type) ?? $type;

        if (! class_exists($class)) {
            return null;
        }

        $query = $class::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        return $query->find($id);
    }
}
