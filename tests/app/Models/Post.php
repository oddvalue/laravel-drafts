<?php

namespace Oddvalue\LaravelDrafts\Tests\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Oddvalue\LaravelDrafts\Database\Factories\PostFactory;

/**
 * @mixes \Oddvalue\LaravelDrafts\Concerns\HasDrafts
 * @use HasFactory<PostFactory>
 */
class Post extends Model
{
    use HasDrafts;
    use HasFactory;

    protected $fillable = ['title'];

    /** @var array<int, string> */
    protected array $draftableRelations = [];

    protected $table = 'posts';

    /**
     * @param array<int, string> $draftableRelations
     */
    public function setDraftableRelations(array $draftableRelations): void
    {
        $this->draftableRelations = $draftableRelations;
    }

    /**
     * @return HasMany<PostSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PostSection::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function morphToTags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * @return HasOne<PostSection, $this>
     */
    public function section(): HasOne
    {
        return $this->hasOne(PostSection::class);
    }

    protected static function newFactory(): PostFactory
    {
        return new PostFactory();
    }
}
