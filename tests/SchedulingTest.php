<?php

use Oddvalue\LaravelDrafts\Tests\Post;
use function Spatie\PestPluginTestTime\testTime;

it('can schedule draft', function () {
    $willPublishAt = now()->addMonth();
    $post = Post::factory()->published()->create();
    $draft = $post->createDraft(['title' => 'Hello World']);
    $draft->schedulePublishing($willPublishAt);
    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => null,
        'will_publish_at' => $willPublishAt,
    ]);
});

it('can publish scheduled drafts', function () {
    $willPublishAt = now()->addWeek();
    $post = Post::factory()->published()->create();
    $draft = $post->createDraft(['title' => 'Hello World']);
    $draft->schedulePublishing($willPublishAt);

    testTime()->addMonth()->freeze();

    \Illuminate\Support\Facades\Artisan::call('drafts:publish', ['model' => Post::class]);

    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => now()->toDateTimeString(),
        'will_publish_at' => null,
    ]);
});
