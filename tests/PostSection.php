<?php

namespace TechnologyAdvice\LaravelDrafts\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostSection extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $guarded = [];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
