<?php

declare(strict_types=1);

namespace Oddvalue\LaravelDrafts\Contracts;

use Carbon\CarbonInterface;

/**
 * The consumer-facing draftable API.
 *
 * The interface is satisfied by the HasDrafts trait; implementing it on a
 * model is optional but recommended, so that user code can typehint against
 * the contract:
 *
 *     class Post extends Model implements Draftable
 *     {
 *         use HasDrafts;
 *     }
 *
 * The contract deliberately covers only the stable public API, not the
 * trait's internal plumbing (setLive, newRevision, setPublisher, ...).
 */
interface Draftable
{
    public function publish(): static;

    public function isPublished(): bool;

    public function isCurrent(): bool;

    /**
     * @param array<string, mixed> $options
     */
    public function saveAsDraft(array $options = []): bool;

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $options
     */
    public function updateAsDraft(array $attributes = [], array $options = []): bool;

    public function asDraft(): static;

    public function schedulePublishing(CarbonInterface $date): static;

    public function clearScheduledPublishing(): static;

    public function getPublishedAtColumn(): string;

    public function getWillPublishAtColumn(): string;

    public function getIsPublishedColumn(): string;

    public function getIsCurrentColumn(): string;

    public function getUuidColumn(): string;
}
