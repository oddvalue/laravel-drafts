<?php

namespace Oddvalue\LaravelDrafts\Concerns;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Oddvalue\LaravelDrafts\Facades\LaravelDrafts;

trait HasDrafts
{
    use Publishes;

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public function initializeHasDrafts()
    {
        $this->mergeCasts([
            $this->getIsCurrentColumn() => 'boolean',
            $this->getPublishedAtColumn() => 'datetime',
        ]);
    }

    public static function bootHasDrafts(): void
    {
        static::creating(function ($model) {
            $model->{$model->getIsCurrentColumn()} = true;
            $model->setPublisher();
            $model->generateUuid();
            if ($model->{$model->getIsPublishedColumn()} !== false) {
                $model->publish();
            }
        });

        static::saving(function ($model) {
        });

        static::updating(function ($model) {
            $model->newRevision();
        });

        static::publishing(function ($model) {
            $model->setLive();
        });

        static::deleted(function ($model) {
            $model->revisions()->delete();
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->revisions()->restore();
            });
        }

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->revisions()->forceDelete();
            });
        }
    }

    protected function newRevision(): void
    {
        if (config('drafts.revisions.keep') === false) {
            return;
        }

        if ($this->fireModelEvent('creatingRevision') === false) {
            return;
        }

        $revision = $this->fresh()->replicate();

        static::saved(function () use ($revision) {
            $revision->created_at = $this->created_at;
            $revision->updated_at = $this->updated_at;
            $revision->is_current = false;
            $revision->is_published = false;

            $revision->saveQuietly(['timestamps' => false]); // Preserve the existing updated_at

            $this->setPublisher();
            $this->pruneRevisions();

            $this->fireModelEvent('createdRevision');
        });
    }

    public function generateUuid(): void
    {
        if ($this->uuid) {
            return;
        }
        $this->uuid = Str::uuid();
    }

    public function setCurrent(): void
    {
        $oldCurrent = $this->revisions()->withDrafts()->current()->notSelf()->first();

        static::saved(function () use ($oldCurrent) {
            if ($oldCurrent) {
                $oldCurrent->{$this->getIsCurrentColumn()} = false;
                $oldCurrent->timestamps = false;
                $oldCurrent->saveQuietly();
            }
        });

        $this->{$this->getIsCurrentColumn()} = true;
    }

    public function setLive(): void
    {
        $oldPublished = $this->revisions()->published()->first();

        static::saved(function () use ($oldPublished) {
            if ($oldPublished) {
                $oldPublished->{$this->getIsPublishedColumn()} = false;
                $oldPublished->timestamps = false;
                $oldPublished->saveQuietly();
            }
        });
        $publishedDate = $oldPublished?->{$this->getPublishedAtColumn()} ?? now();

        $this->{$this->getPublishedAtColumn()} = $publishedDate;
        $this->{$this->getIsPublishedColumn()} = true;
        $this->setCurrent();
    }

    public function saveAsDraft(): bool
    {
        if ($this->fireModelEvent('savingAsDraft') === false || $this->fireModelEvent('saving') === false) {
            return $this;
        }

        $draft = $this->replicate();
        $draft->{$this->getPublishedAtColumn()} = null;
        $draft->{$this->getIsPublishedColumn()} = false;
        $draft->setCurrent();

        if ($saved = $draft->save()) {
            $this->fireModelEvent('savedAsDraft');
            $this->pruneRevisions();
        }

        return $saved;
    }

    public function updateAsDraft(array $attributes = [], array $options = []): bool
    {
        if (! $this->exists) {
            return false;
        }

        return $this->fill($attributes)->saveAsDraft($options);
    }

    public static function createDraft(...$attributes): self
    {
        return tap(static::make(...$attributes), function ($instance) {
            $instance->{$instance->getIsPublishedColumn()} = false;

            return $instance->save();
        });
    }

    public function setPublisher(): static
    {
        if ($this->{$this->getPublisherColumns()['id']} === null && LaravelDrafts::getCurrentUser()) {
            $this->publisher()->associate(LaravelDrafts::getCurrentUser());
        }

        return $this;
    }

    public function pruneRevisions()
    {
        $this->withoutEvents(function () {
            $revisionsToKeep = $this->revisions()
                ->orderByDesc('updated_at')
                ->onlyDrafts()
                ->withoutCurrent()
                ->take(config('drafts.revisions.keep'))
                ->pluck('id')
                ->merge($this->revisions()->current()->pluck('id'))
                ->merge($this->revisions()->published()->pluck('id'));

            $this->revisions()
                ->withDrafts()
                ->whereNotIn('id', $revisionsToKeep)
                ->delete();
        });
    }

    /**
     * Get the name of the "publisher" relation columns.
     *
     * @return array
     */
    #[ArrayShape(['id' => "string", 'type' => "string"])]
    public function getPublisherColumns(): array
    {
        return [
            'id' => defined(static::class.'::PUBLISHER_ID')
                ? static::PUBLISHER_ID
                : config('drafts.column_names.publisher_morph_name', 'publisher') . '_id',
            'type' => defined(static::class.'::PUBLISHER_TYPE')
                ? static::PUBLISHER_TYPE
                : config('drafts.column_names.publisher_morph_name', 'publisher') . '_type',
        ];
    }

    /**
     * Get the fully qualified "publisher" relation columns.
     *
     * @return array
     */
    public function getQualifiedPublisherColumns(): array
    {
        return array_map([$this, 'qualifyColumn'], $this->getPublisherColumns());
    }

    public function getIsCurrentColumn(): string
    {
        return defined(static::class.'::CURRENT_COLUMN')
            ? static::CURRENT_COLUMN
            : config('drafts.column_names.is_current', 'is_current');
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function revisions(): HasMany
    {
        return $this->hasMany(static::class, 'uuid', 'uuid')->withDrafts();
    }

    public function drafts()
    {
        return $this->revisions()->current()->onlyDrafts();
    }

    public function publisher(): MorphTo
    {
        return $this->morphTo(config('drafts.column_names.publisher_morph_name'));
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeCurrent(Builder $query): void
    {
        $query->withDrafts()->where($this->getIsCurrentColumn(), true);
    }

    public function scopeWithoutCurrent(Builder $query): void
    {
        $query->where($this->getIsCurrentColumn(), false);
    }

    public function scopeUuid(Builder $query, $uuid)
    {
        $query->where('uuid', $uuid);
    }

    public function scopeNotSelf(Builder $query)
    {
        $query->where('id', '!=', $this->id);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getDraftAttribute()
    {
        return $this->drafts()->first();
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
