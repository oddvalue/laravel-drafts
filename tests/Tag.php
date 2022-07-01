<?php

namespace Oddvalue\LaravelDrafts\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $guarded = [];

    function taggables()
    {
        return $this->morphTo('taggable');
    }
}
