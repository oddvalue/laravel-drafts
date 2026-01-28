<?php

namespace Oddvalue\LaravelDrafts\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Oddvalue\LaravelDrafts\Scopes\PublishingScope;

/**
 * @method static Builder<static>|\Illuminate\Database\Query\Builder withPublished(bool $withPublished = true)
 * @method static Builder<static>|\Illuminate\Database\Query\Builder onlyPublished()
 * @method static Builder<static>|\Illuminate\Database\Query\Builder withoutPublished()
 */
trait Publishes
{
    /**
     * Boot the publishes trait for a model.
     */
    public static function bootPublishes(): void
    {
        static::addGlobalScope(new PublishingScope());
    }

    /**
     * Initialize the publishes trait for an instance.
     */
    public function initializePublishes(): void
    {
        $this->mergeCasts([
            $this->getPublishedAtColumn() => 'datetime',
            $this->getIsPublishedColumn() => 'boolean',
        ]);
    }

    /**
     * Publish a model instance.
     */
    public function publish(): static
    {
        if ($this->fireModelEvent('publishing') === false) {
            return $this;
        }

        $this->setPublishedAttributes();

        static::saved(function (Model $model): void {
            if ($model->isNot($this)) {
                return;
            }

            $this->fireModelEvent('published');
        });

        return $this;
    }

    protected function setPublishedAttributes(): void
    {
        $this->{$this->getPublishedAtColumn()} ??= now();
        $this->{$this->getIsPublishedColumn()} = true;
    }

    /**
     * Determine if the model instance has been published.
     */
    public function isPublished(): bool
    {
        return $this->{$this->getIsPublishedColumn()} ?? false;
    }

    /**
     * Register a "published" model event callback with the dispatcher.
     */
    public static function publishing(string|Closure $callback): void
    {
        static::registerModelEvent('publishing', $callback);
    }

    /**
     * Register a "softDeleted" model event callback with the dispatcher.
     */
    public static function published(string|Closure $callback): void
    {
        static::registerModelEvent('published', $callback);
    }

    /**
     * Get the name of the "published at" column.
     */
    public function getPublishedAtColumn(): string
    {
        return defined(static::class.'::PUBLISHED_AT')
            ? static::PUBLISHED_AT
            : config('drafts.column_names.published_at', 'published_at');
    }

    /**
     * Get the fully qualified "published at" column.
     */
    public function getQualifiedPublishedAtColumn(): string
    {
        return $this->qualifyColumn($this->getPublishedAtColumn());
    }

    /**
     * Get the name of the "published at" column.
     */
    public function getIsPublishedColumn(): string
    {
        return defined(static::class.'::IS_PUBLISHED')
            ? static::IS_PUBLISHED
            : config('drafts.column_names.is_published', 'is_published');
    }

    /**
     * Get the fully qualified "published at" column.
     */
    public function getQualifiedIsPublishedColumn(): string
    {
        return $this->qualifyColumn($this->getIsPublishedColumn());
    }
}
