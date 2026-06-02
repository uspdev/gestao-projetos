<?php

namespace App\Models;

use Spatie\Tags\Tag as SpatieTag;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Tag extends SpatieTag
{
    use LogsActivity;

    public const TYPE_PROJECT = 'projects';
    public const TYPE_TASK = 'tasks';

    public $fillable = [
        'name',
        'slug',
        'type',
        'order_column',

        //adições
        'color',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tag')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Retorna as tags do tipo "projects"
     */
    public static function forProjects()
    {
        return static::withType(self::TYPE_PROJECT)
            ->orderBy('name')
            ->get();
    }

    /**
     * Retorna as tags do tipo "tasks"
     */
    public static function forTasks()
    {
        return static::withType(self::TYPE_TASK)
            ->orderBy('name')
            ->get();
    }
}
