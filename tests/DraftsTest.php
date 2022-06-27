<?php

use Oddvalue\LaravelDrafts\Tests\Post;

it('creates drafts', function () {
    config(['drafts.revisions.keep' => 2]);
    $this->freezeTime();
    $post = Post::factory()->published()->create(['title' => 'Foo']);
    $newPost = Post::factory()->make(['title' => 'Bar']);
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

it('can create drafts when revisions are disabled', function () {
    config(['drafts.revisions.keep' => 0]);
    $post = Post::factory()->create(['title' => 'Foo']);
    $this->assertDatabaseCount('posts', 1);
    $post->title = 'Bar';
    $post->saveAsDraft();
    $this->assertDatabaseCount('posts', 2);
    $post->title = 'Baz';
    $post->saveAsDraft();
    $this->assertDatabaseCount('posts', 2);
});
