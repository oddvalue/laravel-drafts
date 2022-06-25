<?php

use Oddvalue\LaravelDrafts\Tests\Post;

it('creates drafts', function () {
    $this->freezeTime();
    $post = Post::factory()->published()->create();
    $newPost = Post::factory()->make();
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
