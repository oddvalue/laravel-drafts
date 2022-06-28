<?php

use Oddvalue\LaravelDrafts\Tests\Post;

it('fires publishing event', function () {
    $post = Post::factory()->draft()->create(['title' => 'Draft']);
    Post::publishing(function ($post) {
        $post->title = 'Published';
        return false;
    });
    expect($post->title)->toBe('Draft')
        ->and($post->publish()->save())->toBeTrue()
        ->and($post->fresh()->title)->toBe('Published');
});

it('fires published event', function () {
    $post = Post::factory()->draft()->create(['title' => 'Draft']);
    Post::published(function ($post) {
        $post->title = 'Published';
    });
    expect($post->title)->toBe('Draft')
        ->and($post->publish()->save())->toBeTrue()
        ->and($post->fresh()->title)->toBe('Draft')
        ->and($post->title)->toBe('Published');
});

it('fires savingAsDraft event', function () {
    $post = Post::factory()->create(['title' => 'Published']);
    Post::savingAsDraft(function ($post) {
        $post->title = 'Draft';

        return false;
    });
    expect($post->title)->toBe('Published')
        ->and($post->saveAsDraft())->toBeFalse()
        ->and($post->draft)->toBeNull()
        ->and($post->title)->toBe('Draft')
        ->and($post->fresh()->title)->toBe('Published');
});

it('fires savedAsDraft event', function () {
    $post = Post::factory()->create(['title' => 'Published']);
    Post::savedAsDraft(function ($post) {
        $post->title = 'Draft';
    });
    expect($post->title)->toBe('Published')
        ->and($post->saveAsDraft())->toBeTrue()
        ->and($post->draft->title)->toBe('Published')
        ->and($post->title)->toBe('Draft');
});
