<?php

namespace Oddvalue\LaravelDrafts\Tests;

use Illuminate\Database\Eloquent\Model;
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
