<?php

namespace App\Morphs;

use Illuminate\Database\Eloquent\Model;

class MentionMap extends BaseMorphMap
{
    public static function options(): array
    {
        return array_merge(static::sourceOptions(), static::targetOptions());
    }

    public static function sourceOptions(): array
    {
        return static::configuredOptions('source', [
            'project' => \App\Models\Project::class,
            'task' => \App\Models\Task::class,
            'meeting' => \App\Models\Meeting::class,
            'meeting_item' => \App\Models\MeetingItem::class,
            'comment' => \App\Models\Comment::class,
        ]);
    }

    public static function targetOptions(): array
    {
        return static::configuredOptions('target', [
            'user' => \App\Models\User::class,
            'project' => \App\Models\Project::class,
            'task' => \App\Models\Task::class,
        ]);
    }

    public static function resolveSourceClass(string $type): ?string
    {
        return static::resolveFrom(static::sourceOptions(), $type);
    }

    public static function resolveTargetClass(string $type): ?string
    {
        return static::resolveFrom(static::targetOptions(), $type);
    }

    public static function aliasForSource(Model $source): ?string
    {
        return static::aliasFor(static::sourceOptions(), $source);
    }

    public static function aliasForTarget(Model $target): ?string
    {
        return static::aliasFor(static::targetOptions(), $target);
    }

    protected static function contract(): ?string
    {
        return null;
    }

    private static function resolveFrom(array $options, string $type): ?string
    {
        if (isset($options[$type])) {
            return static::validateClass($options[$type], null);
        }

        return null;
    }

    private static function aliasFor(array $options, Model $model): ?string
    {
        foreach ($options as $alias => $class) {
            if ($model instanceof $class) {
                return (string) $alias;
            }
        }

        return null;
    }

    private static function configuredOptions(string $side, array $fallback): array
    {
        if (! function_exists('app') || ! app()->bound('config')) {
            return $fallback;
        }

        return config('projetos.morphs.mention.' . $side, $fallback);
    }
}
