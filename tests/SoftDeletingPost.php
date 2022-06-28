<?php

namespace Oddvalue\LaravelDrafts\Tests;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeletingPost extends Post
{
    use SoftDeletes;
}
