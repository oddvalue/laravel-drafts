<?php

namespace Oddvalue\LaravelDrafts\Tests\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Oddvalue\LaravelDrafts\Database\Factories\TagFactory;

/**
 * @use HasFactory<TagFactory>
 */
class Tag extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return MorphTo<Model, $this>
     */
    public function taggables(): MorphTo
    {
        return $this->morphTo('taggable');
    }
}
