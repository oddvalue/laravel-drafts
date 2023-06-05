<?php

namespace TechnologyAdvice\LaravelDrafts\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use TechnologyAdvice\LaravelDrafts\Facades\LaravelDrafts;

trait HasDrafts
{
    use Publishes;

    protected bool $shouldCreateRevision = true;

    protected bool $shouldSaveAsDraft = false;

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
        if (
            // Revisions are disabled
            config('drafts.revisions.keep') < 1
            // This model has been set not to create a revision
            || $this->shouldCreateRevision() === false
            // The record is being soft deleted or restored
            || $this->isDirty('deleted_at')
            // A listener of the creatingRevision event returned false
            || $this->fireModelEvent('creatingRevision') === false
        ) {
            return;
        }

        $revision = $this->fresh()->replicate([
            'uuid',
        ]);

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

    private function copyRelationships($model)
    {
        collect($this->getDraftableRelations())->each(function (string $relationName) use ($model) {
            $relation = $model->{$relationName}();
            switch (true) {
                case $relation instanceof HasOne:
                    if ($related = $this->{$relationName}) {
                        $model->{$relationName}()->create($related->replicate()->getAttributes());
                    }

                    break;
                case $relation instanceof HasMany:
                    $this->{$relationName}()->get()->each(function ($relation) use ($model, $relationName) {
                        $model->{$relationName}()->create($relation->replicate()->getAttributes());
                    });

                    break;
                case $relation instanceof MorphToMany:
                case $relation instanceof BelongsToMany:
                    $relationKey = $this->{$relationName}()->getQualifiedRelatedPivotKeyName();
                    $model->{$relationName}()->sync($this->{$relationName}()->pluck($relationKey));

                    break;
            }
        });

        if (method_exists($this, 'saveCustomRelations')) {
            $this->saveCustomRelations($model);
        }
    }
    
    private function deleteRelationships($model)
    {
        collect($this->getDraftableRelations())->each(function (string $relationName) use ($model) {
            $relation = $model->{$relationName}();
            switch (true) {
                case $relation instanceof HasOne:
                    $model->{$relationName}->forceDelete();

                    break;
                case $relation instanceof HasMany:
                    $model->{$relationName}()->get()->each(function ($relation) {
                        $relation->forceDelete();
                    });

                    break;
                case $relation instanceof MorphToMany:
                case $relation instanceof BelongsToMany:
                    $model->{$relationName}()->detach();

                    break;
            }
        });

        if (method_exists($this, 'deleteCustomRelations')) {
            $this->deleteCustomRelations($model);
        }
    }

    public function shouldCreateRevision(): bool
    {
        return $this->shouldCreateRevision;
    }

    public function generateUuid(): void
    {
        if ($this->{$this->getUuidColumn()}) {
            return;
        }
        $this->{$this->getUuidColumn()} = Str::uuid();
    }

    public function setCurrent(): void
    {
        $oldCurrent = $this->revisions()->withDrafts()->current()->where('id', '!=', $this->id)->first();

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
        $published = $this->revisions()->where('id', '!=', $this->id)->published()->first();
        if (! $published) {
            $this->{$this->getPublishedAtColumn()} ??= Carbon::now();
            $this->{$this->getIsPublishedColumn()} = true;
            $this->setCurrent();

            return;
        }

        $oldAttributes = $published?->getAttributes() ?? [];
        $newAttributes = $this->getAttributes();
        Arr::forget($oldAttributes, [$this->getKeyName(), 'uuid']);
        Arr::forget($newAttributes, [$this->getKeyName(), 'uuid']);

        // This logic has been added to prevent casted attributes that are json castable from double encoding on draft publish
        $jsonCastableTypes = ['array', 'json', 'object', 'collection', 'encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object'];
        $castedAttributes = $this->getCasts();
        foreach ($castedAttributes as $attribute=>$type) {
            if (in_array($type, (array) $jsonCastableTypes, true)) {
                $newAttributes[$attribute] = json_decode($newAttributes[$attribute]);
                $oldAttributes[$attribute] = json_decode($oldAttributes[$attribute]);
            }
        }
        // end 

        $published->forceFill($newAttributes);
        $this->forceFill($oldAttributes);

        static::saved(function () use ($published) {
            $published->{$this->getIsPublishedColumn()} = true;
            $published->{$this->getPublishedAtColumn()} ??= Carbon::now();
            $published->setCurrent();
            $published->saveQuietly();

            $this->deleteRelationships($published);
            $this->copyRelationships($published);
        });

        $this->{$this->getIsPublishedColumn()} = false;
        $this->{$this->getPublishedAtColumn()} = null;
        $this->{$this->getIsCurrentColumn()} = false;
        $this->timestamps = false;
        $this->shouldCreateRevision = false;
    }

    public function getDraftableRelations(): array
    {
        return property_exists($this, 'draftableRelations') ? $this->draftableRelations : [];
    }

    public function saveAsDraft(array $options = []): bool
    {
        if ($this->fireModelEvent('savingAsDraft') === false || $this->fireModelEvent('saving') === false) {
            return false;
        }

        $draft = $this->replicate([
            'uuid',
        ]);

        $draft->{$this->getPublishedAtColumn()} = null;
        $draft->{$this->getIsPublishedColumn()} = false;
        $draft->shouldSaveAsDraft = false;
        $draft->setCurrent();

        if ($saved = $draft->save($options)) {
            $draft->refresh();
            $this->copyRelationships($draft);

            $this->fireModelEvent('drafted');
            $this->pruneRevisions();
        }

        return $saved;
    }

    public function asDraft(): static
    {
        $this->shouldSaveAsDraft = true;

        return $this;
    }

    public function shouldDraft(): bool
    {
        return $this->shouldSaveAsDraft;
    }

    public function save(array $options = []): bool
    {
        if (
            $this->exists
            && (
                data_get($options, 'draft') || $this->shouldDraft()
            )
        ) {
            return $this->saveAsDraft($options);
        }

        return parent::save($options);
    }

    public static function savingAsDraft(string|\Closure $callback): void
    {
        static::registerModelEvent('savingAsDraft', $callback);
    }

    public static function savedAsDraft(string|\Closure $callback): void
    {
        static::registerModelEvent('drafted', $callback);
    }

    public function updateAsDraft(array $attributes = [], array $options = []): bool
    {
        if (! $this->exists) {
            return false;
        }

        return $this->fill($attributes)->saveAsDraft($options);
    }

    public function makeDraft($params = [])
    {
        $this->updateAsDraft($params);
        return self::whereDraftUuid($this->draft_uuid)
            ->current()
            ->first();
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

            $revisionsToDelete = $this->revisions()
                ->withDrafts()
                ->whereNotIn('id', $revisionsToKeep);

            if ($this->forceDeleteRevisions) {
                $revisionsToDelete->get()->each(function ($revision) {
                    $this->deleteRelationships($revision);
                    $revision->forceDelete();
                });
            }
            else {
                $revisionsToDelete->delete();
            }
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
        return defined(static::class.'::IS_CURRENT')
            ? static::IS_CURRENT
            : config('drafts.column_names.is_current', 'is_current');
    }

    public function getUuidColumn(): string
    {
        return defined(static::class.'::UUID')
            ? static::UUID
            : config('drafts.column_names.uuid', 'uuid');
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function revisions(): HasMany
    {
        return $this->hasMany(static::class, $this->getUuidColumn(), $this->getUuidColumn())->withDrafts();
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

    /** DOES NOT WORK AS INTENDED. $this is not the model instance but the class so $this->id doesn't exist */
    // public function scopeWithoutSelf(Builder $query)
    // {
    //     $query->where('id', '!=', $this->id);
    // }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getDraftAttribute()
    {
        if ($this->relationLoaded('drafts')) {
            return $this->drafts->first();
        }
        if ($this->relationLoaded('revisions')) {
            return $this->revisions->firstWhere($this->getIsCurrentColumn(), true);
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
