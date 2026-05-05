<?php

namespace App\Models;

use Spatie\Tags\Tag as SpatieTag;

class Tag extends SpatieTag
{
    public $fillable = [
        'name',
        'slug',
        'type',
        'order_column',

        //adições
        'color',
        'description',
    ];


    public const TYPE_PROJECT = 'projects';
    public const TYPE_TASK = 'tasks';

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
