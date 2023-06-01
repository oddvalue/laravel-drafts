<?php

use Illuminate\Support\Facades\Artisan;
use Oddvalue\LaravelDrafts\Tests\Post;
use Oddvalue\LaravelDrafts\Tests\SchedulingPost;

use function Spatie\PestPluginTestTime\testTime;

it('can schedule draft', function () {
    $willPublishAt = now()->addMonth();
    $post = SchedulingPost::factory()->published()->create();
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
    $post = SchedulingPost::factory()->published()->create();
    $draft = $post->createDraft(['title' => 'Hello World']);
    $draft->schedulePublishing($willPublishAt);

    testTime()->addMonth()->freeze();

    Artisan::call('drafts:publish', ['model' => SchedulingPost::class]);

    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => now()->toDateTimeString(),
        'will_publish_at' => null,
    ]);
});

it('fails when the model doesnt implement the contract', function (): void {
    expect(static fn () => Artisan::call('drafts:publish', ['model' => Post::class]))
        ->toThrow(InvalidArgumentException::class);
});

it('can clear the schedule date on a revision', function (): void {
    $willPublishAt = now()->addWeek();
    $post = SchedulingPost::factory()
        ->published()
        ->has(
            SchedulingPost::factory(),
            'revisions'
        )
        ->create();
    $draft = $post->createDraft(['title' => 'Hello World']);
    $draft->schedulePublishing($willPublishAt);
    $draft->clearScheduledPublishing()->save();

    testTime()->addMonth()->freeze();

    Artisan::call('drafts:publish', ['model' => SchedulingPost::class]);

    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => null,
        'will_publish_at' => null,
    ]);
});
