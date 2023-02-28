<?php

namespace Oddvalue\LaravelDrafts\Tests;

use Illuminate\Database\Eloquent\SoftDeletes;
use Oddvalue\LaravelDrafts\Database\Factories\SoftDeletingPostFactory;

class SoftDeletingPost extends Post
{
    use SoftDeletes;

    protected $table = 'soft_deleting_posts';

    protected static function newFactory()
    {
        return new SoftDeletingPostFactory();
    }
}
