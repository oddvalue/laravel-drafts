<?php

use Oddvalue\LaravelDrafts\Facades\LaravelDrafts;
use Oddvalue\LaravelDrafts\Tests\app\Models\Post;

use function Spatie\PestPluginTestTime\testTime;

it('can draft model', function (): void {
    Post::createDraft(['title' => 'Hello World']);
    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => null,
    ]);
});

it('can publish a draft model', function (): void {
    testTime()->freeze();
    Post::query()->create(['title' => 'Hello World']);
    Post::withDrafts()->first()->save();
    $this->assertDatabaseHas('posts', [
        'published_at' => now()->toDateTimeString(),
    ]);
});

it('can publish a model', function (): void {
    testTime()->freeze();
    Post::query()->make(['title' => 'Hello World'])->save();
    $this->assertDatabaseHas('posts', [
        'title' => 'Hello World',
        'published_at' => now()->toDateTimeString(),
    ]);
});

it('omits drafts from default query', function (): void {
    Post::factory()->count(5)->create();
    Post::factory()->count(5)->draft()->create();
    $this->assertCount(5, Post::all());
});

it('can use `withDrafts` scope to select drafts', function (): void {
    Post::factory()->count(5)->published()->create();
    Post::factory()->count(5)->draft()->create();
    expect(Post::withDrafts()->pluck('id'))
        ->toHaveCount(10);
});

it('generates a uuid', function (): void {
    $post = Post::factory()->create();
    $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $post->uuid);
});

it('has a method to check published status', function (): void {
    $post = Post::factory()->create();
    expect($post->isPublished())->toBeTrue();

    $post = Post::factory()->draft()->create();
    expect($post->isPublished())->toBeFalse();
});

it('does not create multiple published records', function (): void {
    $post = Post::factory()->create();
    $post->title = 'b';
    $post->saveAsDraft();
    $post->draft->publish()->save();
    expect(Post::withDrafts()->pluck('id'))
        ->toHaveCount(2);
    expect(Post::withoutDrafts()->pluck('id'))
        ->toHaveCount(1);
});

it('can publish a draft that is not the current one', function (): void {
    LaravelDrafts::withDrafts();

    Post::factory()->create(['title' => 'a']);

    $post = Post::query()->where('title', 'a')->first();
    $post->title = 'b';

    $b = $post->saveAsDraft();

    $post = Post::query()->where('title', 'b')->first();
    $post->title = 'c';
    $post->saveAsDraft();

    expect(Post::query()->where('title', 'a')->first()->isPublished())->toBeTrue();
    expect(Post::query()->where('title', 'c')->first()->isCurrent())->toBeTrue();

    $draftB = Post::query()->where('title', 'b')->first();
    $draftB->setLive();
    $draftB->save();

    expect(Post::query()->where('title', 'a')->first()->isPublished())->toBeFalse();
    expect(Post::query()->where('title', 'b')->first()->isPublished())->toBeTrue();
    expect(Post::query()->current()->count())->toBe(1);
});
