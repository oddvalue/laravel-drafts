<?php

namespace Oddvalue\LaravelDrafts\Tests\app\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Oddvalue\LaravelDrafts\Database\Factories\SoftDeletingPostFactory;

/**
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<SoftDeletingPostFactory>
 */
class SoftDeletingPost extends Post
{
    use SoftDeletes;

    protected $table = 'soft_deleting_posts';

    protected static function newFactory(): SoftDeletingPostFactory
    {
        return new SoftDeletingPostFactory();
    }
}
