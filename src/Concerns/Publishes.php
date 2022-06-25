<?php

namespace Oddvalue\LaravelDrafts\Concerns;

use Oddvalue\LaravelDrafts\Scopes\PublishingScope;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withPublished(bool $withPublished = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlyPublished()
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withoutPublished()
 */
trait Publishes
{
    /**
     * Boot the publishes trait for a model.
     *
     * @return void
     */
    public static function bootPublishes(): void
    {
        static::addGlobalScope(new PublishingScope());
    }

    /**
     * Initialize the publishes trait for an instance.
     *
     * @return void
     */
    public function initializePublishes(): void
    {
        if (! isset($this->casts[$this->getPublishedAtColumn()])) {
            $this->casts[$this->getPublishedAtColumn()] = 'datetime';
        }
    }

    /**
     * Publish a model instance.
     *
     * @return static
     */
    public function publish(): static
    {
        if ($this->fireModelEvent('publishing') === false) {
            return $this;
        }

        $this->{$this->getPublishedAtColumn()} = now();

        static::saved(function ($model) {
            $model->fireModelEvent('published');
        });

        return $this;
    }

    /**
     * Determine if the model instance has been published.
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->{$this->getPublishedAtColumn()}?->isPast() ?? false;
    }

    /**
     * Register a "softDeleted" model event callback with the dispatcher.
     *
     * @param string|\Closure $callback
     * @return void
     */
    public static function savedAsDraft(string|\Closure $callback): void
    {
        static::registerModelEvent('drafted', $callback);
    }

    /**
     * Register a "published" model event callback with the dispatcher.
     *
     * @param string|\Closure $callback
     * @return void
     */
    public static function publishing(string|\Closure $callback): void
    {
        static::registerModelEvent('publishing', $callback);
    }

    /**
     * Register a "softDeleted" model event callback with the dispatcher.
     *
     * @param string|\Closure $callback
     * @return void
     */
    public static function published(string|\Closure $callback): void
    {
        static::registerModelEvent('published', $callback);
    }

    /**
     * Get the name of the "published at" column.
     *
     * @return string
     */
    public function getPublishedAtColumn(): string
    {
        return defined(static::class.'::PUBLISHED_AT')
            ? static::PUBLISHED_AT
            : config('drafts.column_names.published_at', 'published_at');
    }

    /**
     * Get the fully qualified "published at" column.
     *
     * @return string
     */
    public function getQualifiedPublishedAtColumn(): string
    {
        return $this->qualifyColumn($this->getPublishedAtColumn());
    }
}
