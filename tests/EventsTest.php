<?php

use Oddvalue\LaravelDrafts\Tests\app\Models\Post;

it('fires publishing event', function (): void {
    $post = Post::factory()->draft()->create(['title' => 'Draft']);
    Post::publishing(function ($post): void {
        $post->title = 'Published';
    });
    expect($post->title)->toBe('Draft')
        ->and($post->publish()->save())->toBeTrue()
        ->and($post->fresh()->title)->toBe('Published');
});

it('fires published event', function (): void {
    $post = Post::factory()->draft()->create(['title' => 'Draft']);
    Post::published(function ($post): void {
        $post->title = 'Published';
    });
    expect($post->title)->toBe('Draft')
        ->and($post->publish()->save())->toBeTrue()
        ->and($post->fresh()->title)->toBe('Draft')
        ->and($post->title)->toBe('Published');
});

it('fires savingAsDraft event', function (): void {
    $post = Post::factory()->create(['title' => 'Published']);
    Post::savingAsDraft(function ($post): bool {
        $post->title = 'Draft';

        return false;
    });
    expect($post->title)->toBe('Published')
        ->and($post->saveAsDraft())->toBeFalse()
        ->and($post->draft)->toBeNull()
        ->and($post->title)->toBe('Draft')
        ->and($post->fresh()->title)->toBe('Published');
});

it('fires savedAsDraft event', function (): void {
    $post = Post::factory()->create(['title' => 'Published']);
    Post::savedAsDraft(function ($post): void {
        $post->title = 'Draft';
    });
    expect($post->title)->toBe('Published')
        ->and($post->saveAsDraft())->toBeTrue()
        ->and($post->draft->title)->toBe('Published')
        ->and($post->title)->toBe('Draft');
});
