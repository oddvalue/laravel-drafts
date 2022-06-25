<?php

use Oddvalue\LaravelDrafts\Tests\Post;

it('can draft model', function () {
    Post::create(['title' => 'Hello World']);
    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => null,
    ]);
});

it('can publish a draft model', function () {
    $this->freezeTime();
    Post::create(['title' => 'Hello World']);
    Post::withDrafts()->first()->publish()->save();
    $this->assertDatabaseHas('posts', [
        'published_at' => now()->toDateTimeString(),
    ]);
});

it('can publish a model', function () {
    $this->freezeTime();
    Post::make(['title' => 'Hello World'])->publish()->save();
    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => now()->toDateTimeString(),
    ]);
});

it('omits drafts from default query', function () {
    Post::factory()->count(5)->published()->create();
    Post::factory()->count(5)->draft()->create();
    $this->assertCount(5, Post::all());
});

it('can use `withDrafts` scope to select drafts', function () {
    Post::factory()->count(5)->published()->create();
    Post::factory()->count(5)->draft()->create();
    $this->assertCount(10, Post::withDrafts()->get());
});

it('generates a uuid', function () {
    $post = Post::factory()->create();
    $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $post->uuid);
});
