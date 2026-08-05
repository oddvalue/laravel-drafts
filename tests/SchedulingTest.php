<?php

use Illuminate\Support\Facades\Artisan;
use Oddvalue\LaravelDrafts\Tests\app\Models\Post;
use Oddvalue\LaravelDrafts\Tests\app\Models\User;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function (): void {
    config(['drafts.scheduled_drafts.enabled' => true]);
});

it('can schedule a draft', function (): void {
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

it('can publish scheduled drafts', function (): void {
    $willPublishAt = now()->addWeek();
    $post = Post::factory()->published()->create();
    $draft = $post->createDraft(['title' => 'Hello World']);
    $draft->schedulePublishing($willPublishAt);

    testTime()->addMonth()->freeze();

    Artisan::call('drafts:publish', ['model' => Post::class]);

    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => now()->toDateTimeString(),
        'will_publish_at' => null,
    ]);
});

it('does not publish drafts scheduled for the future', function (): void {
    $post = Post::factory()->published()->create();
    $draft = $post->createDraft(['title' => 'Hello World']);
    $draft->schedulePublishing(now()->addMonth());

    Artisan::call('drafts:publish', ['model' => Post::class]);

    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => null,
    ]);
});

it('can clear the scheduled publish date', function (): void {
    $willPublishAt = now()->addWeek();
    $post = Post::factory()->published()->create();
    $draft = $post->createDraft(['title' => 'Hello World']);
    $draft->schedulePublishing($willPublishAt);
    $draft->clearScheduledPublishing()->save();

    testTime()->addMonth()->freeze();

    Artisan::call('drafts:publish', ['model' => Post::class]);

    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => null,
        'will_publish_at' => null,
    ]);
});

it('clears the scheduled publish date when a draft is published directly', function (): void {
    $post = Post::factory()->published()->create();
    $draft = $post->createDraft(['title' => 'Hello World']);
    $draft->schedulePublishing(now()->addMonth());

    $draft->fresh()->publish()->save();

    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'will_publish_at' => null,
    ]);
});

it('fails when the class does not use the HasDrafts trait', function (): void {
    expect(static fn () => Artisan::call('drafts:publish', ['model' => User::class]))
        ->toThrow(InvalidArgumentException::class);
});

it('fails when the class does not exist', function (): void {
    expect(static fn () => Artisan::call('drafts:publish', ['model' => 'App\\Models\\Nonexistent']))
        ->toThrow(InvalidArgumentException::class);
});

it('fails when scheduled drafts are disabled', function (): void {
    config(['drafts.scheduled_drafts.enabled' => false]);

    expect(static fn () => Artisan::call('drafts:publish', ['model' => Post::class]))
        ->toThrow(InvalidArgumentException::class)
        ->and(static fn () => Post::factory()->published()->create()->schedulePublishing(now()->addWeek()))
        ->toThrow(LogicException::class)
        ->and(static fn () => Post::factory()->published()->create()->clearScheduledPublishing())
        ->toThrow(LogicException::class);
});
