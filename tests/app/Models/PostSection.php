<?php

namespace Oddvalue\LaravelDrafts\Tests\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Oddvalue\LaravelDrafts\Database\Factories\PostSectionFactory;

/**
 * @phpstan-use HasFactory<PostSectionFactory>
 */
class PostSection extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
