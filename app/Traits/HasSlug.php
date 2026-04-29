<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

trait HasSlug
{
    public static function bootHasSlug()
    {
        static::creating(function (Model $model) {
            $model->generateSlug();
        });

        static::updating(function (Model $model) {
            // Só regenera se o campo base mudou (ex: name/title)
            if ($model->isDirty($model->slugSourceColumn())) {
                $model->generateSlug();
            }
        });
    }

    protected function generateSlug()
    {
        $slugColumn = $this->slugColumn();
        $sourceColumn = $this->slugSourceColumn();

        // Se já tem slug e não quer sobrescrever
        if (!empty($this->$slugColumn)) {
            return;
        }

        $base = Str::slug($this->$sourceColumn);

        // fallback caso string vazia
        if (empty($base)) {
            $base = 'item';
        }

        $slug = $base;
        $i = 1;

        while ($this->slugExists($slug)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        $this->$slugColumn = $slug;
    }

    protected function slugExists($slug): bool
    {
        $query = static::where($this->slugColumn(), $slug);

        // Ignora o próprio registro no update
        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }

    protected function slugColumn(): string
    {
        return property_exists($this, 'slugColumn') ? $this->slugColumn : 'slug';
    }

    protected function slugSourceColumn(): string
    {
        return property_exists($this, 'slugSourceColumn') ? $this->slugSourceColumn : 'name';
    }
}
