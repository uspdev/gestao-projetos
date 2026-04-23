<?php

namespace App\Models;

use Spatie\Tags\Tag as SpatieTag;

/**
 * @property int $id
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed> $slug
 * @property string|null $type
 * @property int|null $order_column
 * @property string $color
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $translations
 * @method static Builder<static>|Tag containing(string $name, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static Builder<static>|Tag ordered(string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static Builder<static>|Tag whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Tag whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Tag whereLocale(string $column, string $locale)
 * @method static Builder<static>|Tag whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereOrderColumn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedAt($value)
 * @method static Builder<static>|Tag withType(?string $type = null)
 * @mixin \Eloquent
 */
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
}