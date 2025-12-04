<?php

namespace Oddvalue\LaravelDrafts\Concerns;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Oddvalue\LaravelDrafts\Facades\LaravelDrafts;

/**
 * @method static Builder | Model current()
 * @method static Builder | Model withoutCurrent()
 * @method static Builder | Model excludeRevision(int | Model $exclude)
 *
 * @mixin Model
 */
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

    public function initializeHasDrafts(): void
    {
        $this->mergeCasts([
            $this->getIsCurrentColumn() => 'boolean',
            $this->getIsPublishedColumn() => 'boolean',
            $this->getPublishedAtColumn() => 'datetime',
        ]);
    }

    public static function bootHasDrafts(): void
    {
        static::addGlobalScope('onlyCurrentInPreviewMode', static function (Builder $builder): void {
            if (LaravelDrafts::isPreviewModeEnabled()) {
                /** @phpstan-ignore method.notFound */
                $builder->current();
            }
        });

        static::creating(function (Model $model): void {
            /** @phpstan-ignore method.notFound */
            $model->{$model->getIsCurrentColumn()} = true;
            /** @phpstan-ignore method.notFound */
            $model->setPublisher();
            /** @phpstan-ignore method.notFound */
            $model->generateUuid();
            /** @phpstan-ignore method.notFound */
            if ($model->{$model->getIsPublishedColumn()} !== false) {
                /** @phpstan-ignore method.notFound */
                $model->publish();
            }
        });

        static::updating(function (Model $model): void {
            /** @phpstan-ignore method.notFound */
            $model->newRevision();
        });

        static::publishing(function (Model $model): void {
            /** @phpstan-ignore method.notFound */
            $model->setLive();
        });

        static::deleted(function (Model $model): void {
            /** @phpstan-ignore method.notFound, method.nonObject */
            $model->revisions()->delete();
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model): void {
                /** @phpstan-ignore method.notFound, method.nonObject */
                $model->revisions()->restore();
            });
        }

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(function (Model $model): void {
                /** @phpstan-ignore method.notFound, method.nonObject */
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
            /** @phpstan-ignore argument.type */
            || $this->isDirty(method_exists($this, 'getDeletedAtColumn') ? $this->getDeletedAtColumn() : 'deleted_at')
            // A listener of the creatingRevision event returned false
            || $this->fireModelEvent('creatingRevision') === false
        ) {
            return;
        }

        $updatingModel = $this->fresh();
        $revision = $updatingModel?->replicate();

        static::saved(function (Model $model) use ($updatingModel, $revision): void {
            if ($model->isNot($this) || $revision === null || $updatingModel === null) {
                return;
            }

            $revision->{$this->getCreatedAtColumn()} = $updatingModel->{$this->getCreatedAtColumn()};
            $revision->{$this->getUpdatedAtColumn()} = $updatingModel->{$this->getUpdatedAtColumn()};
            $revision->{$this->getIsCurrentColumn()} = false;
            $revision->{$this->getIsPublishedColumn()} = false;

            $revision->saveQuietly(['timestamps' => false]); // Preserve the existing updated_at

            $this->setPublisher();
            $this->pruneRevisions();

            $this->fireModelEvent('createdRevision');
        });
    }

    public function withoutRevision(): static
    {
        $this->shouldCreateRevision = false;

        return $this;
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

    /**
     * @return array<string, mixed>
     */
    public function getDraftableAttributes(): array
    {
        return $this->getAttributes();
    }

    public function setCurrent(): void
    {
        $this->{$this->getIsCurrentColumn()} = true;

        static::saved(function (Model $model): void {
            if ($model->isNot($this)) {
                return;
            }

            // @phpstan-ignore-next-line method.notFound, method.nonObject
            $this->revisions()->withDrafts()->current()->excludeRevision($this)->update([$this->getIsCurrentColumn() => false]);
        });
    }

    public function setLive(): void
    {
        /** @phpstan-ignore method.notFound, method.nonObject */
        $published = $this->revisions()->published()->first();

        /** @phpstan-ignore argument.type */
        if (! $published || $this->is($published)) {
            $this->{$this->getPublishedAtColumn()} ??= now();
            $this->{$this->getIsPublishedColumn()} = true;
            $this->setCurrent();

            return;
        }

        /** @phpstan-ignore method.nonObject, nullsafe.neverNull */
        $oldAttributes = $published?->getDraftableAttributes() ?? [];
        $newAttributes = $this->getDraftableAttributes();
        /** @phpstan-ignore argument.type */
        Arr::forget($oldAttributes, $this->getKeyName());
        Arr::forget($newAttributes, $this->getKeyName());

        /** @phpstan-ignore method.nonObject */
        $published->forceFill($newAttributes);
        /** @phpstan-ignore argument.type */
        $this->forceFill($oldAttributes);

        static::saved(function (Model $model) use ($published): void {
            if ($model->isNot($this)) {
                return;
            }

            /** @phpstan-ignore method.nonObject */
            $published->{$this->getIsPublishedColumn()} = true;
            /** @phpstan-ignore method.nonObject */
            $published->{$this->getPublishedAtColumn()} ??= now();
            /** @phpstan-ignore method.nonObject */
            $published->setCurrent();
            /** @phpstan-ignore method.nonObject */
            $published->saveQuietly();

            /** @phpstan-ignore argument.type */
            $this->replicateAndAssociateDraftableRelations($published);
        });

        $this->{$this->getIsPublishedColumn()} = false;
        $this->{$this->getPublishedAtColumn()} = null;
        $this->{$this->getIsCurrentColumn()} = false;
        $this->timestamps = false;
        $this->shouldCreateRevision = false;
    }

    public function replicateAndAssociateDraftableRelations(Model $published): void
    {
        collect($this->getDraftableRelations())->each(function (string $relationName) use ($published): void {
            $relation = $published->{$relationName}();
            switch (true) {
                case $relation instanceof HasOne:
                    if ($related = $this->{$relationName}) {
                        /** @phpstan-ignore method.nonObject */
                        $replicated = $related->replicate();

                        /** @phpstan-ignore argument.type */
                        $method = method_exists($replicated, 'getDraftableAttributes')
                            ? 'getDraftableAttributes'
                            : 'getAttributes';

                        // @phpstan-ignore-next-line method.nonObject
                        $published->{$relationName}()->create($replicated->$method());
                    }

                    break;
                case $relation instanceof HasMany:
                    // @phpstan-ignore-next-line method.nonObject
                    $this->{$relationName}()->get()->each(function ($model) use ($published, $relationName): void {
                        // @phpstan-ignore-next-line method.nonObject
                        $replicated = $model->replicate();

                        /** @phpstan-ignore argument.type */
                        $method = method_exists($replicated, 'getDraftableAttributes')
                            ? 'getDraftableAttributes'
                            : 'getAttributes';

                        // @phpstan-ignore-next-line method.nonObject
                        $published->{$relationName}()->create($replicated->$method());
                    });

                    break;
                case $relation instanceof MorphToMany:
                case $relation instanceof BelongsToMany:
                    // @phpstan-ignore-next-line method.nonObject
                    $published->{$relationName}()->sync($this->{$relationName}()->pluck('id'));

                    break;
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function getDraftableRelations(): array
    {
        /** @phpstan-ignore function.alreadyNarrowedType */
        return property_exists($this, 'draftableRelations') ? $this->draftableRelations : [];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function saveAsDraft(array $options = []): bool
    {
        if ($this->fireModelEvent('savingAsDraft') === false || $this->fireModelEvent('saving') === false) {
            return false;
        }

        $draft = $this->replicate();
        $draft->{$this->getPublishedAtColumn()} = null;
        $draft->{$this->getIsPublishedColumn()} = false;
        $draft->shouldSaveAsDraft = false;
        $draft->setCurrent();

        if ($saved = $draft->save($options)) {
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

    public function setPublishedAttributes(): void
    {
        // Do nothing, everything should be handled by `setLive`
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

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $options
     */
    public function updateAsDraft(array $attributes = [], array $options = []): bool
    {
        if (! $this->exists) {
            return false;
        }

        return $this->fill($attributes)->saveAsDraft($options);
    }

    /**
     * @param array<string, mixed> ...$attributes
     * @return static
     */
    public static function createDraft(...$attributes): self
    {
        /** @phpstan-ignore return.type */
        return tap(static::make(...$attributes), function ($instance) {
            /** @phpstan-ignore argument.type */
            $instance->{$instance->getIsPublishedColumn()} = false;

            return $instance->save();
        });
    }

    public function setPublisher(): static
    {
        $currentUser = LaravelDrafts::getCurrentUser();
        if ($this->{$this->getPublisherColumns()['id']} === null && $currentUser instanceof Model) {
            $this->publisher()->associate($currentUser);
        }

        return $this;
    }

    public function pruneRevisions(): void
    {
        self::withoutEvents(function (): void {
            // @phpstan-ignore-next-line method.notFound, method.nonObject
            $revisionsToKeep = $this->revisions()->orderByDesc($this->getUpdatedAtColumn() ?? 'updated_at')->onlyDrafts()->withoutCurrent()->take(config('drafts.revisions.keep'))->pluck('id')->merge($this->revisions()->current()->pluck('id'))->merge($this->revisions()->published()->pluck('id'));

            // @phpstan-ignore-next-line method.notFound, method.nonObject
            $this->revisions()->withDrafts()->whereNotIn('id', $revisionsToKeep)->delete();
        });
    }

    /**
     * Get the name of the "publisher" relation columns.
     */
    #[ArrayShape(['id' => "string", 'type' => "string"])]
    /**
     * @return array{id: string, type: string}
     */
    public function getPublisherColumns(): array
    {
        /** @var string $morphName */
        $morphName = config('drafts.column_names.publisher_morph_name', 'publisher');

        return [
            'id' => defined(static::class.'::PUBLISHER_ID')
                ? static::PUBLISHER_ID
                : $morphName . '_id',
            'type' => defined(static::class.'::PUBLISHER_TYPE')
                ? static::PUBLISHER_TYPE
                : $morphName . '_type',
        ];
    }

    /**
     * Get the fully qualified "publisher" relation columns.
     *
     * @return array{id: string, type: string}
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
        return defined(static::class . '::UUID')
            ? static::UUID
            : config('drafts.column_names.uuid', 'uuid');
    }

    public function isCurrent(): bool
    {
        return $this->{$this->getIsCurrentColumn()} ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * @return HasMany<static, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(static::class, $this->getUuidColumn(), $this->getUuidColumn())->withDrafts();
    }

    /**
     * @return HasMany<static, $this>
     */
    public function drafts(): HasMany
    {
        return $this->revisions()->current()->onlyDrafts();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function publisher(): MorphTo
    {
        /** @var string|null $morphName */
        $morphName = config('drafts.column_names.publisher_morph_name');

        return $this->morphTo($morphName);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * @param Builder<Model> $query
     */
    public function scopeCurrent(Builder $query): void
    {
        /** @phpstan-ignore method.notFound, method.nonObject */
        $query->withDrafts()->where($this->getIsCurrentColumn(), true);
    }

    /**
     * @param Builder<Model> $query
     */
    public function scopeWithoutCurrent(Builder $query): void
    {
        $query->where($this->getIsCurrentColumn(), false);
    }

    /**
     * @param Builder<Model> $query
     */
    public function scopeExcludeRevision(Builder $query, int | Model $exclude): void
    {
        $query->where($this->getKeyName(), '!=', is_int($exclude) ? $exclude : $exclude->getKey());
    }

    /**
     * @deprecated This doesn't actually work, will be removed in next version
     * @param Builder<Model> $query
     */
    public function scopeWithoutSelf(Builder $query): void
    {
        /** @phpstan-ignore argument.type */
        $query->where('id', '!=', $this->id);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * @return static|null
     */
    public function getDraftAttribute(): ?self
    {
        if ($this->relationLoaded('drafts')) {
            /** @phpstan-ignore return.type */
            return $this->drafts->first();
        }

        if ($this->relationLoaded('revisions')) {
            /** @phpstan-ignore return.type */
            return $this->revisions->firstWhere($this->getIsCurrentColumn(), true);
        }

        /** @phpstan-ignore return.type */
        return $this->drafts()->first();
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
