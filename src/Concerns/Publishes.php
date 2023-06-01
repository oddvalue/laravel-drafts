<?php

namespace Oddvalue\LaravelDrafts\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Oddvalue\LaravelDrafts\Scopes\PublishingScope;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withDrafts(bool $withDrafts = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withoutDrafts()
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlyDrafts()
 */
trait Publishes
{
    public static function bootPublishes(): void
    {
        static::addGlobalScope(new PublishingScope());
    }

    public function initializePublishes(): void
    {
        $this->mergeCasts([
            $this->getPublishedAtColumn() => 'datetime',
            $this->getIsPublishedColumn() => 'boolean',
        ]);
    }

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

    public function isPublished(): bool
    {
        return $this->{$this->getIsPublishedColumn()} ?? false;
    }

    public static function publishing(string|Closure $callback): void
    {
        static::registerModelEvent('publishing', $callback);
    }

    public static function published(string|Closure $callback): void
    {
        static::registerModelEvent('published', $callback);
    }

    public function getPublishedAtColumn(): string
    {
        return defined(static::class.'::PUBLISHED_AT')
            ? static::PUBLISHED_AT
            : config('drafts.column_names.published_at', 'published_at');
    }

    public function getQualifiedPublishedAtColumn(): string
    {
        return $this->qualifyColumn($this->getPublishedAtColumn());
    }

    public function getIsPublishedColumn(): string
    {
        return defined(static::class.'::IS_PUBLISHED')
            ? static::IS_PUBLISHED
            : config('drafts.column_names.is_published', 'is_published');
    }

    public function getQualifiedIsPublishedColumn(): string
    {
        return $this->qualifyColumn($this->getIsPublishedColumn());
    }
}
