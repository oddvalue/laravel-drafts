<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Oddvalue\LaravelDrafts\Tests\app\Models\Post;

/*
 * Regression tests for installations upgrading from a version without auto
 * draft support: while drafts.auto_drafts.enabled is off (the default), no
 * query may reference the is_auto column, so every draft feature must keep
 * working against a table that does not have the column at all.
 */

beforeEach(function (): void {
    config(['drafts.auto_drafts.enabled' => false]);

    Schema::table('posts', function (Blueprint $table): void {
        $table->dropColumn('is_auto');
    });
});

it('creates and updates records without the is_auto column', function (): void {
    config(['drafts.revisions.keep' => 5]);

    $post = Post::factory()->create(['title' => 'Rev 0']);
    for ($i = 1; $i <= 5; $i++) {
        $post->fresh()->update(['title' => 'Rev ' . $i]);
    }

    $this->assertDatabaseCount(Post::class, 6);
    expect(Post::query()->find($post->getKey())->title)->toBe('Rev 5');
});

it('prunes revisions without the is_auto column', function (): void {
    config(['drafts.revisions.keep' => 2]);

    $post = Post::factory()->create(['title' => 'Rev 0']);
    for ($i = 1; $i <= 5; $i++) {
        $post->fresh()->update(['title' => 'Rev ' . $i]);
    }

    // The current revision plus the two kept draft revisions
    $this->assertDatabaseCount(Post::class, 3);
});

it('supports intentional drafts and the draft accessor without the is_auto column', function (): void {
    $post = Post::factory()->create(['title' => 'Live']);
    $post->fresh()->updateAsDraft(['title' => 'Draft']);

    $post = $post->fresh();
    expect($post->title)->toBe('Live')
        ->and($post->draft)->not->toBeNull()
        ->and($post->draft->title)->toBe('Draft')
        ->and($post->drafts()->toSql())->not->toContain('is_auto')
        ->and($post->revisions()->toSql())->not->toContain('is_auto');
});

it('publishes a draft without the is_auto column', function (): void {
    $post = Post::factory()->create(['title' => 'Live']);
    $post->fresh()->updateAsDraft(['title' => 'Draft']);

    $post->fresh()->draft->publish()->save();

    expect($post->fresh()->title)->toBe('Draft');
});

it('creates draft records without the is_auto column', function (): void {
    Post::createDraft(['title' => 'Foo']);

    expect(Post::query()->count())->toBe(0)
        ->and(Post::withDrafts()->count())->toBe(1);
});

it('deletes records and their revisions without the is_auto column', function (): void {
    config(['drafts.revisions.keep' => 5]);

    $post = Post::factory()->create(['title' => 'Foo']);
    $post->fresh()->update(['title' => 'Bar']);

    $this->assertDatabaseCount(Post::class, 2);

    $post->fresh()->delete();

    $this->assertDatabaseCount(Post::class, 0);
});
