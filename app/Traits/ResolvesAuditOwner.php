<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait ResolvesAuditOwner
{
    private static function resolveOwner(string $class, mixed $id): ?Model
    {
        if (! $id) return null;
        $query = $class::query();
        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }
        return $query->find($id);
    }
}
