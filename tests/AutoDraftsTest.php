<?php

use Oddvalue\LaravelDrafts\Tests\app\Models\Post;

beforeEach(function (): void {
    config(['drafts.auto_drafts.enabled' => true]);
});

it('saves an auto draft without altering the record', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);

    $autoDraft = $post->saveAsAutoDraft(['title' => 'Bar']);

    $this->assertDatabaseCount('posts', 2);
    expect($post->fresh())
        ->title->toBe('Foo')
        ->isCurrent()->toBeTrue()
        ->isPublished()->toBeTrue()
        ->and($autoDraft)
        ->title->toBe('Bar')
        ->isAutoDraft()->toBeTrue()
        ->isCurrent()->toBeFalse()
        ->isPublished()->toBeFalse()
        ->published_at->toBeNull();
});

it('updates the auto draft in place on subsequent saves', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);

    $first = $post->saveAsAutoDraft(['title' => 'Bar']);
    $second = $post->saveAsAutoDraft(['title' => 'Baz']);

    $this->assertDatabaseCount('posts', 2);
    expect($second->getKey())->toBe($first->getKey())
        ->and($post->autoDraft->title)->toBe('Baz');
});

it('does not spawn revisions when saving an auto draft', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);

    $post->saveAsAutoDraft(['title' => 'Bar']);
    $post->saveAsAutoDraft(['title' => 'Baz']);

    expect($post->revisions()->withoutCurrent()->onlyAutoDrafts()->count())->toBe(1)
        ->and($post->revisions()->withoutCurrent()->withoutAutoDrafts()->count())->toBe(0);
});

it('fetches the auto draft through the autoDraft relation', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);
    $post->saveAsAutoDraft(['title' => 'Bar']);

    expect($post->autoDraft)->not->toBeNull()
        ->and($post->autoDraft->title)->toBe('Bar');

    $post->load('autoDraft');
    expect($post->autoDraft->title)->toBe('Bar');
});

it('excludes auto drafts from the drafts relation and draft accessor', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);
    $post->saveAsAutoDraft(['title' => 'Bar']);

    expect($post->drafts()->count())->toBe(0)
        ->and($post->fresh()->draft)->toBeNull();
});

it('keeps intentional drafts separate from the auto draft', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);
    $post->saveAsAutoDraft(['title' => 'Auto']);
    $post->fresh()->updateAsDraft(['title' => 'Intentional']);

    expect($post->fresh()->draft->title)->toBe('Intentional')
        ->and($post->autoDraft->title)->toBe('Auto');
});

it('excludes the auto draft from revision pruning', function (): void {
    config(['drafts.revisions.keep' => 1]);
    $post = Post::factory()->create(['title' => 'Foo']);
    $post->saveAsAutoDraft(['title' => 'Auto']);

    $post = $post->fresh();
    $post->update(['title' => 'Bar']);
    $post->update(['title' => 'Baz']);
    $post->update(['title' => 'Qux']);

    expect($post->autoDraft)->not->toBeNull()
        ->and($post->autoDraft->title)->toBe('Auto');
});

it('does not publish the auto draft when the record is published', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);
    $post->saveAsAutoDraft(['title' => 'Auto']);

    $post->fresh()->updateAsDraft(['title' => 'Intentional']);
    $post->fresh()->draft->publish()->save();

    expect($post->fresh()->title)->toBe('Intentional')
        ->and($post->autoDraft)->not->toBeNull()
        ->and($post->autoDraft->title)->toBe('Auto')
        ->and($post->autoDraft->isPublished())->toBeFalse()
        ->and($post->autoDraft->isCurrent())->toBeFalse();
});

it('discards the auto draft without touching other revisions', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);
    $post->saveAsAutoDraft(['title' => 'Auto']);
    $post->fresh()->updateAsDraft(['title' => 'Intentional']);

    $post->discardAutoDraft();

    expect($post->autoDraft()->count())->toBe(0)
        ->and($post->fresh()->draft->title)->toBe('Intentional')
        ->and(Post::query()->find($post->getKey())->title)->toBe('Foo');
});

it('discarding is a no-op when there is no auto draft', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);

    $post->discardAutoDraft();

    $this->assertDatabaseCount('posts', 1);
});

it('deletes the auto draft when the record is deleted', function (): void {
    $post = Post::factory()->create(['title' => 'Foo']);
    $post->saveAsAutoDraft(['title' => 'Auto']);

    $post->delete();

    $this->assertDatabaseCount('posts', 0);
});

it('throws when saving an auto draft for an unsaved record', function (): void {
    $post = Post::factory()->make(['title' => 'Foo']);

    $post->saveAsAutoDraft(['title' => 'Auto']);
})->throws(LogicException::class);

it('can save an auto draft for an unpublished draft record', function (): void {
    $post = Post::createDraft(['title' => 'Foo']);

    $autoDraft = $post->saveAsAutoDraft(['title' => 'Auto']);

    $this->assertDatabaseCount('posts', 2);
    expect($autoDraft->isAutoDraft())->toBeTrue()
        ->and($post->fresh()->isCurrent())->toBeTrue()
        ->and($post->autoDraft->title)->toBe('Auto');
});

it('throws when saving an auto draft while auto drafts are disabled', function (): void {
    config(['drafts.auto_drafts.enabled' => false]);
    $post = Post::factory()->create(['title' => 'Foo']);

    $post->saveAsAutoDraft(['title' => 'Auto']);
})->throws(LogicException::class, 'Auto drafts are disabled.');

it('throws when discarding an auto draft while auto drafts are disabled', function (): void {
    config(['drafts.auto_drafts.enabled' => false]);
    $post = Post::factory()->create(['title' => 'Foo']);

    $post->discardAutoDraft();
})->throws(LogicException::class, 'Auto drafts are disabled.');

it('throws when querying auto drafts while auto drafts are disabled', function (): void {
    config(['drafts.auto_drafts.enabled' => false]);

    Post::query()->onlyAutoDrafts()->get();
})->throws(LogicException::class, 'Auto drafts are disabled.');
