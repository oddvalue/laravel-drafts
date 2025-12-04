<?php

namespace Oddvalue\LaravelDrafts\Tests\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Oddvalue\LaravelDrafts\Database\Factories\TagFactory;

/**
 * @phpstan-use HasFactory<TagFactory>
 */
class Tag extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function taggables(): MorphTo
    {
        return $this->morphTo('taggable');
    }
}
