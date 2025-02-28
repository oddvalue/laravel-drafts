<?php

use Oddvalue\LaravelDrafts\Facades\LaravelDrafts;
use Oddvalue\LaravelDrafts\Tests\app\Models\Post;

it('can enable preview mode', function (): void {
    LaravelDrafts::previewMode();
    expect(LaravelDrafts::isPreviewModeEnabled())->toBeTrue();
});

it('can disable preview mode', function (): void {
    LaravelDrafts::previewMode();
    expect(LaravelDrafts::isPreviewModeEnabled())->toBeTrue();
    LaravelDrafts::disablePreviewMode();
    expect(LaravelDrafts::isPreviewModeEnabled())->toBeFalse();
});

it('gets the current draft when preview mode is enabled', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);
    $post->updateAsDraft(['title' => 'Bar']);
    $post->updateAsDraft(['title' => 'Baz']);
    $post->updateAsDraft(['title' => 'Qux']);

    expect(Post::query()->where('uuid', $post->uuid)->first()->title)->toBe('Foo');
    LaravelDrafts::previewMode();
    expect(Post::query()->where('uuid', $post->uuid)->first()->title)->toBe('Qux');
});
