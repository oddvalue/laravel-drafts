<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Oddvalue\LaravelDrafts\Tests\app\Models\Post;

/*
 * Regression tests for installations upgrading from a version without
 * scheduled draft support: while drafts.scheduled_drafts.enabled is off (the
 * default), no query may reference the will_publish_at column, so every draft
 * feature must keep working against a table that does not have the column at
 * all.
 */

beforeEach(function (): void {
    config(['drafts.scheduled_drafts.enabled' => false]);

    Schema::table('posts', function (Blueprint $table): void {
        $table->dropColumn('will_publish_at');
    });
});

it('creates and updates records without the will_publish_at column', function (): void {
    config(['drafts.revisions.keep' => 5]);

    $post = Post::factory()->create(['title' => 'Rev 0']);
    for ($i = 1; $i <= 5; $i++) {
        $post->fresh()->update(['title' => 'Rev ' . $i]);
    }

    $this->assertDatabaseCount(Post::class, 6);
    expect(Post::query()->find($post->getKey())->title)->toBe('Rev 5');
});

it('publishes a draft without the will_publish_at column', function (): void {
    $post = Post::factory()->create(['title' => 'Live']);
    $post->fresh()->updateAsDraft(['title' => 'Draft']);

    $post->fresh()->draft->publish()->save();

    expect($post->fresh()->title)->toBe('Draft');
});

it('creates draft records without the will_publish_at column', function (): void {
    Post::createDraft(['title' => 'Foo']);

    expect(Post::query()->count())->toBe(0)
        ->and(Post::withDrafts()->count())->toBe(1);
});
