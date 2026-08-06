<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Artisan;
use Oddvalue\LaravelDrafts\Contracts\Draftable;
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
        'is_published' => true,
        'published_at' => now()->toDateTimeString(),
        'will_publish_at' => null,
    ]);
});

it('publishes every scheduled draft across multiple batches', function (): void {
    Post::factory()->count(3)->draft()->create([
        'will_publish_at' => now()->subMinute(),
    ]);

    Artisan::call('drafts:publish', ['model' => Post::class, '--chunk' => 2]);

    expect(Post::query()->count())->toBe(3)
        ->and(Post::onlyDrafts()->whereNotNull('will_publish_at')->count())->toBe(0);
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

it('fails when the class implements the contract but is not an Eloquent model', function (): void {
    expect(static fn () => Artisan::call('drafts:publish', ['model' => ContractOnlyDraftable::class]))
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

/**
 * A deliberately non-Eloquent implementation, proving the drafts:publish
 * command rejects classes that satisfy the contract but lack the trait's
 * query APIs. The unused parameters are required by the interface.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ContractOnlyDraftable implements Draftable
{
    public function publish(): static
    {
        return $this;
    }

    public function isPublished(): bool
    {
        return false;
    }

    public function isCurrent(): bool
    {
        return false;
    }

    public function saveAsDraft(array $options = []): bool
    {
        return true;
    }

    public function updateAsDraft(array $attributes = [], array $options = []): bool
    {
        return true;
    }

    public function asDraft(): static
    {
        return $this;
    }

    public function schedulePublishing(CarbonInterface $date): static
    {
        return $this;
    }

    public function clearScheduledPublishing(): static
    {
        return $this;
    }

    public function getPublishedAtColumn(): string
    {
        return 'published_at';
    }

    public function getWillPublishAtColumn(): string
    {
        return 'will_publish_at';
    }

    public function getIsPublishedColumn(): string
    {
        return 'is_published';
    }

    public function getIsCurrentColumn(): string
    {
        return 'is_current';
    }

    public function getUuidColumn(): string
    {
        return 'uuid';
    }
}
