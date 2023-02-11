<?php

namespace Oddvalue\LaravelDrafts\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Oddvalue\LaravelDrafts\Contacts\Draftable;
use Oddvalue\LaravelDrafts\Database\Factories\PostFactory;

/**
 * This Draftable trait was added after v1 was tagged, this model should be used for all new tests and existing
 * functionality must be tested against Post to ensure backwards compatibility.
 */
class SchedulingPost extends Post implements Draftable
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'posts';

    protected static function newFactory(): PostFactory
    {
        return new PostFactory();
    }
}
