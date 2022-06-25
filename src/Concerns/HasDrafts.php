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

    public static function bootHasDrafts(): void
    {
        static::creating(function ($model) {
            $model->is_current = true;
            $model->setPublisher();
        });

        static::saving(function ($model) {
            $model->generateUuid();
        });

        static::updating(function ($model) {
            $model->newRevision();
        });

        static::updated(function ($model) {
            $model->setCurrent();
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
        $this->withoutEvents(function () {
            $revision = $this->fresh()->replicate();
            $revision->created_at = $this->created_at;
            $revision->updated_at = $this->updated_at;

            $revision->save(['timestamps' => false]); // Preserve the existing updated_at

            $this->setPublisher();
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
        $this->withoutEvents(function () {
            // This has to be updated manually as with update() there's no way to prevent timestamp updates,
            // which breaks the history of the updated_at timestamp
            $oldCurrent = $this->revisions()->where('is_current', true)->first();
            if ($oldCurrent) {
                $oldCurrent->is_current = false;
                $oldCurrent->timestamps = false;
                $oldCurrent->save();
            }

            $this->setAttribute('is_current', true)->save();
        });
    }

    public function setLive(): void
    {
        $this->withoutEvents(function () {
            // This has to be updated manually as with update() there's no way to prevent timestamp updates,
            // which breaks the history of the updated_at timestamp
            $oldPublished = $this->revisions()->whereNotNull('published_at')->first();
            if ($oldPublished) {
                $oldPublished->published_at = null;
                $oldPublished->timestamps = false;
                $oldPublished->save();
            }

            $this->published_at = now();
            $this->setCurrent();
        });
    }

    public function saveAsDraft()
    {
        $this->withoutEvents(function () {
            if ($this->fireModelEvent('savingAsDraft') === false || $this->fireModelEvent('saving') === false) {
                return $this;
            }

            if ($this->is_current) {
                $this->is_current = false;
                $this->save();
            } else {
                $this->revisions()->where('is_current', true)->update(['is_current' => false]);
            }
            $draft = $this->replicate();
            $draft->published_at = null;
            $draft->is_current = true;
            $draft->save();
            $this->fireModelEvent('savedAsDraft');
            $this->fireModelEvent('saved');

            return $draft;
        });
    }

    public function setPublisher(): static
    {
        if (! $this->getPublisherColumns()['id']) {
            $this->publisher()->associate(LaravelDrafts::getCurrentUser());
        }

        return $this;
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

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function revisions(): HasMany
    {
        return $this->hasMany(static::class, 'uuid', 'uuid')->where('id', '<>', $this->id)->orderBy('updated_at');
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
        $query->where('is_current', true);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('published_at', true);
    }

    public function scopeWithUnpublished(Builder $query): void
    {
        $query->withoutGlobalScope('published');
    }

    public function scopeOnlyUnpublished(Builder $query)
    {
        $query->withoutGlobalScope('published')->where('published_at', false);
    }

    public function scopeUuid(Builder $query, $uuid)
    {
        $query->where('uuid', $uuid);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
