<?php

namespace Oddvalue\LaravelDrafts\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Oddvalue\LaravelDrafts\Database\Factories\PostFactory;

class Post extends Model
{
    use HasDrafts;
    use HasFactory;

    protected $fillable = ['title'];

    protected array $draftableRelations = [];

    protected $table = 'posts';

    /**
     * @param array $draftableRelations
     */
    public function setDraftableRelations(array $draftableRelations): void
    {
        $this->draftableRelations = $draftableRelations;
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PostSection::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function morphToTags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function section(): HasOne
    {
        return $this->hasOne(PostSection::class);
    }

    protected static function newFactory()
    {
        return new PostFactory();
    }
}
