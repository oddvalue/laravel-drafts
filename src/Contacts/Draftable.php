<?php

namespace Oddvalue\LaravelDrafts\Contacts;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Contracts\Database\Query\Builder;

interface Draftable
{
    public function publish(): static;

    public function save(array $options = []): bool;

    public function isPublished(): bool;

    public static function publishing(string|Closure $callback): void;

    public static function published(string|Closure $callback): void;

    public function getPublishedAtColumn(): string;

    public function getQualifiedPublishedAtColumn(): string;

    public function getIsPublishedColumn(): string;

    public function getQualifiedIsPublishedColumn(): string;

    public function shouldCreateRevision(): bool;

    public function generateUuid(): void;

    public function setCurrent(): void;

    public function setLive(): void;

    public function schedulePublishing(CarbonInterface $date): static;

    public function clearScheduledPublishing(): static;

    public function getDraftableRelations(): array;

    public function saveAsDraft(array $options = []): bool;

    public function asDraft(): static;

    public function shouldDraft(): bool;

    public static function savingAsDraft(string|Closure $callback): void;

    public static function savedAsDraft(string|Closure $callback): void;

    public function updateAsDraft(array $attributes = [], array $options = []): bool;

    public static function createDraft(...$attributes): self;

    public function setPublisher(): static;

    public function pruneRevisions(): void;

    public function getPublisherColumns(): array;

    public function getQualifiedPublisherColumns(): array;

    public function getIsCurrentColumn(): string;

    public function getWillPublishAtColumn(): string;

    public function getUuidColumn(): string;

    public function revisions(): HasMany;

    public function drafts();

    public function publisher(): MorphTo;

    public function getDraftAttribute();
}
