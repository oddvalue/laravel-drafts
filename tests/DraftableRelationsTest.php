<?php

use Illuminate\Support\Facades\DB;
use Oddvalue\LaravelDrafts\Tests\app\Models\Post;
use Oddvalue\LaravelDrafts\Tests\app\Models\PostSection;
use Oddvalue\LaravelDrafts\Tests\app\Models\Tag;

it('can draft HasMany relations', function (): void {
    $post = Post::factory()->create([
        'title' => 'Foo',
    ]);
    $post->updateAsDraft([
        'title' => 'Bar',
    ]);
    $draft = $post->draft;
    $draft->sections()->createMany(PostSection::factory(2)->make()->toArray());

    expect($draft->fresh()->sections)->toHaveCount(2)
        ->and($post->fresh()->sections)->toHaveCount(0);

    $draft->setDraftableRelations(['sections']);
    $draft->publish()->save();

    expect($draft->fresh()->sections)->toHaveCount(2)
        ->and($post->fresh()->sections)->toHaveCount(2)
        ->and(PostSection::count())->toBe(4);
});

it('can draft BelongsToMany relations', function (): void {
    $post = Post::factory()->create([
        'title' => 'Foo',
    ]);
    $post->updateAsDraft([
        'title' => 'Bar',
    ]);
    $draft = $post->draft;
    $draft->tags()->createMany(Tag::factory(2)->make()->toArray());

    expect($draft->fresh()->tags)->toHaveCount(2)
        ->and($post->fresh()->tags)->toHaveCount(0);

    $draft->setDraftableRelations(['tags']);
    $draft->publish()->save();

    expect($draft->fresh()->tags)->toHaveCount(2)
        ->and($post->fresh()->tags)->toHaveCount(2)
        ->and(Tag::count())->toBe(2)
        ->and(DB::table('post_tag')->count())->toBe(4);
});

it('can draft MorphToMany relations', function (): void {
    $post = Post::factory()->create([
        'title' => 'Foo',
    ]);
    $post->updateAsDraft([
        'title' => 'Bar',
    ]);
    $draft = $post->draft;
    $draft->morphToTags()->createMany(Tag::factory(2)->make()->toArray());

    expect($draft->fresh()->morphToTags)->toHaveCount(2)
        ->and($post->fresh()->morphToTags)->toHaveCount(0);

    $draft->setDraftableRelations(['morphToTags']);
    $draft->publish()->save();

    expect($draft->fresh()->morphToTags)->toHaveCount(2)
        ->and($post->fresh()->morphToTags)->toHaveCount(2)
        ->and(Tag::count())->toBe(2)
        ->and(DB::table('taggables')->count())->toBe(4);
});

it('can draft HasOne relations', function (): void {
    $post = Post::factory()->create([
        'title' => 'Foo',
    ]);
    $post->updateAsDraft([
        'title' => 'Bar',
    ]);
    $draft = $post->draft;
    $draft->section()->create(PostSection::factory()->make()->toArray());

    expect($draft->fresh()->section)->toBeInstanceOf(PostSection::class)
        ->and($post->fresh()->section)->toBeNull();

    $draft->setDraftableRelations(['section']);
    $draft->publish()->save();

    expect($draft->fresh()->section)->toBeInstanceOf(PostSection::class)
        ->and($post->fresh()->section)->toBeInstanceOf(PostSection::class)
        ->and(PostSection::count())->toBe(2);
});
