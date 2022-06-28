<?php

use Oddvalue\LaravelDrafts\Tests\Post;
use Oddvalue\LaravelDrafts\Tests\SoftDeletingPost;

it('keeps the correct number of revisions', function () {
    config(['drafts.revisions.keep' => 3]);
    $revsExist = function (...$titles) {
        $this->assertDatabaseCount('posts', count($titles));
        foreach ($titles as $title) {
            $this->assertDatabaseHas('posts', [
                'title' => $title,
            ]);
        }
    };

    config(['drafts.revisions.keep' => 3]);
    $post = Post::factory()->create(['title' => 'Rev 1']);
    $revsExist('Rev 1');
    $this->travel(1)->minutes();

    $post->title = 'Rev 2';
    $post->save();
    $revsExist('Rev 1', 'Rev 2');
    $this->travel(1)->minutes();

    $post->fresh()->update(['title' => 'Rev 3']);
    $revsExist('Rev 1', 'Rev 2', 'Rev 3');
    $this->travel(1)->minutes();

    $post->fresh()->update(['title' => 'Rev 4']);
    $revsExist('Rev 1', 'Rev 2', 'Rev 3', 'Rev 4');
    $this->travel(1)->minutes();

    $post->fresh()->update(['title' => 'Rev 5']);
    $revsExist('Rev 2', 'Rev 3', 'Rev 4', 'Rev 5');
    $this->assertDatabaseMissing('posts', [
        'title' => 'Rev 1',
    ]);
});

it('can disable revisions', function () {
    config(['drafts.revisions.keep' => 0]);
    $post = Post::factory()->create(['title' => 'Foo']);
    $this->assertDatabaseCount('posts', 1);
    $post->update(['title' => 'Bar']);
    $this->assertDatabaseCount('posts', 1);
});

it('deletes revisions', function () {
    config(['drafts.revisions.keep' => 5]);

    $post = Post::factory()->create();
    for ($i = 0; $i < 5; $i++) {
        $post->fresh()->update(['title' => "Title {$i}"]);
    }

    $this->assertDatabaseCount(Post::class, 6);

    $post->delete();

    $this->assertDatabaseCount(Post::class, 0);
});

it('soft deletes revisions', function () {
    config(['drafts.revisions.keep' => 5]);

    $post = SoftDeletingPost::factory()->create();
    for ($i = 0; $i < 5; $i++) {
        $post->fresh()->update(['title' => "Title {$i}"]);
    }

    $this->assertDatabaseCount(SoftDeletingPost::class, 6);

    $post->delete();

    $this->assertDatabaseCount(SoftDeletingPost::class, 6);
    expect(Post::withDrafts()->count())->toBe(0);

    $post->forceDelete();

    $this->assertDatabaseCount(SoftDeletingPost::class, 0);
});

it('retores soft deleted revisions', function () {
    config(['drafts.revisions.keep' => 5]);

    $post = SoftDeletingPost::factory()->create();
    for ($i = 0; $i < 5; $i++) {
        $post->fresh()->update(['title' => "Title {$i}"]);
    }

    $this->assertDatabaseCount(SoftDeletingPost::class, 6);

    $post->delete();

    $this->assertDatabaseCount(SoftDeletingPost::class, 6);
    expect(SoftDeletingPost::withDrafts()->count())->toBe(0);

    $post->restore();

    $this->assertDatabaseCount(SoftDeletingPost::class, 6);
    expect(SoftDeletingPost::withDrafts()->count())->toBe(6);
});
