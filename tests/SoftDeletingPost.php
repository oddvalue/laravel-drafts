<?php

namespace Oddvalue\LaravelDrafts\Tests;

use Illuminate\Database\Eloquent\SoftDeletes;
use Oddvalue\LaravelDrafts\Database\Factories\PostFactory;

class SoftDeletingPost extends Post
{
    use SoftDeletes;
}
