<?php

use Oddvalue\LaravelDrafts\Tests\Post;
use function Spatie\PestPluginTestTime\testTime;

it('creates drafts', function () {
    config(['drafts.revisions.keep' => 2]);
    testTime()->freeze();
    $post = Post::factory()->published()->create(['title' => 'Foo']);
    $newPost = Post::factory()->make(['title' => 'Bar']);
    $this->assertDatabaseCount('posts', 1);
    $this->assertDatabaseHas('posts', [
        'title' => $post->title,
        'published_at' => now()->toDateTimeString(),
        'is_current' => true,
    ]);
    $post->title = $newPost->title;
    $post->saveAsDraft();
    $this->assertDatabaseCount('posts', 2);

    $this->assertDatabaseHas('posts', [
        'title' => $post->getOriginal('title'),
        'published_at' => now()->toDateTimeString(),
        'is_current' => false,
    ]);
    $this->assertDatabaseHas('posts', [
        'title' => $newPost->title,
        'published_at' => null,
        'is_current' => true,
    ]);
});

it('can create drafts when revisions are disabled', function () {
    config(['drafts.revisions.keep' => 0]);
    $post = Post::factory()->create(['title' => 'Foo']);
    $this->assertDatabaseCount('posts', 1);
    $post->title = 'Bar';
    $post->saveAsDraft();
    $this->assertDatabaseCount('posts', 2);
    $post->title = 'Baz';
    $post->saveAsDraft();
    $this->assertDatabaseCount('posts', 2);
});

it('can fetch the draft of a published record', function () {
    $post = Post::factory()->create();
    $draft = Post::factory()->make();
    $post->fresh()->updateAsDraft(['title' => $draft->title]);

    expect($post->fresh()->title)->toBe($post->title);
    expect($post->draft->title)->toBe($draft->title);
});

it('can publish drafts', function () {
    $post = Post::factory()->create(['title' => 'Foo']);
    $draft = Post::factory()->make(['title' => 'Bar']);

    testTime()->addMinute();

    $post->fresh()->updateAsDraft(['title' => $draft->title]);

    testTime()->addMinute();

    $post->draft->publish()->save();

    expect($post->fresh()->title)->toBe($draft->title);
});

it('returns false when calling update on a record that has not been persisted', function () {
    $post = Post::factory()->make();
    expect($post->updateAsDraft(['title' => 'Foo']))->toBeFalse();
});

it('gets draft record from loaded revisions relation', function () {
    $post = Post::factory()->create(['title' => 'Foo']);
    $draft = Post::factory()->make(['title' => 'Bar']);
    $post->fresh()->updateAsDraft(['title' => $draft->title]);

    $post->load('revisions');
    expect($post->draft->title)->toBe($draft->title);
});

it('gets draft record from loaded draft relation', function () {
    $post = Post::factory()->create(['title' => 'Foo']);
    $draft = Post::factory()->make(['title' => 'Bar']);
    $post->fresh()->updateAsDraft(['title' => $draft->title]);

    $post->load('drafts');
    expect($post->draft->title)->toBe($draft->title);
});

it('gets draft record when no relations loaded', function () {
    $post = Post::factory()->create(['title' => 'Foo']);
    $draft = Post::factory()->make(['title' => 'Bar']);
    $post->fresh()->updateAsDraft(['title' => $draft->title]);

    expect($post->draft->title)->toBe($draft->title);
});

it('can create draft using default save method', function () {
    $post = Post::factory()->create(['title' => 'Foo']);
    $draft = Post::factory()->make(['title' => 'Bar']);
    $post->refresh();
    $post->title = $draft->title;
    $post->asDraft()->save();

    expect($post->fresh()->title)->toBe('Foo');
    expect($post->draft->title)->toBe($draft->title);
});
