<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Mention extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'source_field',
        'target_type',
        'target_id',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    public function target(): MorphTo
    {
        return $this->morphTo('target');
    }
}
