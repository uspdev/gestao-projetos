<?php

namespace App\Support;

use App\Contracts\Discussable;
use Illuminate\Database\Eloquent\Model;

class MorphTypes
{
    public static function discussableOptions(): array
    {
        return config('projetos.morphs.discussable', []);
    }

    public static function commentableOptions(): array
    {
        return config('projetos.morphs.commentable', []);
    }

    public static function allowedDiscussableValues(): array
    {
        return self::allowedValues(self::discussableOptions(), Discussable::class);
    }

    public static function allowedCommentableValues(): array
    {
        return self::allowedValues(self::commentableOptions(), null);
    }

    public static function resolveDiscussableClass(string $type): ?string
    {
        return self::resolveClass($type, self::discussableOptions(), Discussable::class);
    }

    public static function resolveCommentableClass(string $type): ?string
    {
        return self::resolveClass($type, self::commentableOptions(), null);
    }

    public static function morphMap(): array
    {
        $options = array_merge(self::discussableOptions(), self::commentableOptions());
        $map = [];

        fore    $options as $alias => $class) {
            $validatedClass = self::validateClass($class, null);
            if ($validatedClass) {
                $map[$alias] = $validatedClass;
            }
        }

        return $map;
    }

    private static function allowedValues(array $options, ?string $contract): array
    {
        $values = [];

        foreach ($options as $alias => $class) {
            $validatedClass = self::validateClass($class, $contract);
            if (!$validatedClass) {
                continue;
            }

            // Permite tanto o apelido (ex: 'task') quanto a classe completa (ex: 'App\Models\Task')
            $values[] = (string) $alias;
            $values[] = $validatedClass;
        }

        return array_values(array_unique($values));
    }

    private static function resolveClass(string $type, array $options, ?string $contract): ?string
    {
        if ($type === '') {
            return null;
        }

        // 1. Correspondência exata pelo apelido (ex: 'task')
        if (isset($options[$type])) {
            return self::validateClass($options[$type], $contract);
        }

        // 2. Correspondência exata pela classe FQCN (ex: 'App\Models\Task')
        $typeWithoutSlash = ltrim($type, '\\');
        if (in_array($typeWithoutSlash, $options, true)) {
            return self::validateClass($typeWithoutSlash, $contract);
        }

        return null;
    }

    private static function validateClass(mixed $class, ?string $contract): ?string
    {
        if (!is_string($class) || $class === '') {
            return null;
        }

        $class = ltrim($class, '\\');

        if (!class_exists($class)) {
            return null;
        }

        if (!is_subclass_of($class, Model::class)) {
            return null;
        }

        if ($contract && !is_subclass_of($class, $contract)) {
            return null;
        }

        return $class;
    }
}