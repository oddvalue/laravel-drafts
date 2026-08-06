<?php

namespace Oddvalue\LaravelDrafts\Concerns;

use Carbon\CarbonInterface;
use Closure;
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
use LogicException;
use Oddvalue\LaravelDrafts\Facades\LaravelDrafts;

/**
 * @template TModel of Model
 *
 * @method static Builder<TModel> | TModel current()
 * @method static Builder<TModel> | TModel withoutCurrent()
 * @method static Builder<TModel> | TModel excludeRevision(int | TModel $exclude)
 * @method static Builder<TModel> | TModel onlyAutoDrafts()
 * @method static Builder<TModel> | TModel withoutAutoDrafts()
 *
 * @mixin TModel
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

        if (static::autoDraftsEnabled()) {
            $this->mergeCasts([
                $this->getIsAutoColumn() => 'boolean',
            ]);
        }

        if (static::scheduledDraftsEnabled()) {
            $this->mergeCasts([
                $this->getWillPublishAtColumn() => 'datetime',
            ]);
        }
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
            // Auto drafts are ephemeral working copies and never spawn revisions
            || $this->isAutoDraft()
            // The record is being soft deleted or restored
            /** @phpstan-ignore argument.type */
            || $this->isDirty(method_exists($this, 'getDeletedAtColumn') ? $this->getDeletedAtColumn() : 'deleted_at')
            // A listener of the creatingRevision event returned false
            || $this->fireModelEvent('creatingRevision') === false
        ) {
            return;
        }

        $updatingModel = $this->fresh();

        if ($updatingModel === null) {
            return;
        }

        $revision = $updatingModel->replicate();

        static::saved(function (Model $model) use ($updatingModel, $revision): void {
            if ($model->isNot($this)) {
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
            if (static::scheduledDraftsEnabled()) {
                $this->{$this->getWillPublishAtColumn()} = null;
            }

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
            if (static::scheduledDraftsEnabled()) {
                /** @phpstan-ignore method.nonObject */
                $published->{$this->getWillPublishAtColumn()} = null;
            }

            /** @phpstan-ignore method.nonObject */
            $published->setCurrent();
            /** @phpstan-ignore method.nonObject */
            $published->saveQuietly();

            /** @phpstan-ignore argument.type */
            $this->replicateAndAssociateDraftableRelations($published);
        });

        $this->{$this->getIsPublishedColumn()} = false;
        $this->{$this->getPublishedAtColumn()} = null;
        if (static::scheduledDraftsEnabled()) {
            $this->{$this->getWillPublishAtColumn()} = null;
        }

        $this->{$this->getIsCurrentColumn()} = false;
        $this->timestamps = false;
        $this->shouldCreateRevision = false;
    }

    /**
     * Schedule the record to be published at the given date by the
     * `drafts:publish` command.
     */
    public function schedulePublishing(CarbonInterface $date): static
    {
        throw_unless(static::scheduledDraftsEnabled(), LogicException::class, 'Scheduled drafts are disabled. Set the drafts.scheduled_drafts.enabled config option to true to use them.');

        $this->{$this->getWillPublishAtColumn()} = $date;
        $this->save();

        return $this;
    }

    /**
     * Remove the record's scheduled publish date. The change is not
     * persisted; call `save()` afterwards.
     */
    public function clearScheduledPublishing(): static
    {
        throw_unless(static::scheduledDraftsEnabled(), LogicException::class, 'Scheduled drafts are disabled. Set the drafts.scheduled_drafts.enabled config option to true to use them.');

        $this->{$this->getWillPublishAtColumn()} = null;

        return $this;
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

    public static function savingAsDraft(string|Closure $callback): void
    {
        static::registerModelEvent('savingAsDraft', $callback);
    }

    public static function savedAsDraft(string|Closure $callback): void
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
     * Create or update the record's auto draft.
     *
     * The auto draft is a single, quietly saved working copy of the record —
     * intended for auto-save/recovery features. It is upserted in place on
     * every call, is never flagged as current or published and never spawns
     * revisions, so the record, any intentional drafts and the revision
     * history are left untouched.
     *
     * @param array<string, mixed> $attributes
     */
    public function saveAsAutoDraft(array $attributes = []): static
    {
        throw_unless(static::autoDraftsEnabled(), LogicException::class, 'Auto drafts are disabled. Set the drafts.auto_drafts.enabled config option to true to use them.');
        throw_unless($this->exists, LogicException::class, 'An auto draft can only be saved for an existing record.');

        /** @var static|null $autoDraft */
        $autoDraft = $this->autoDraft()->first();
        $autoDraft ??= $this->replicate();

        $autoDraft->forceFill([
            ...$attributes,
            $this->getIsCurrentColumn() => false,
            $this->getIsPublishedColumn() => false,
            $this->getPublishedAtColumn() => null,
            $this->getIsAutoColumn() => true,
        ]);

        $autoDraft->saveQuietly();

        return $autoDraft;
    }

    /**
     * Delete the record's auto draft, if one exists.
     *
     * Deletes through the query builder on purpose: an Eloquent delete on a
     * HasDrafts model cascades to every revision sharing the record's uuid.
     */
    public function discardAutoDraft(): void
    {
        throw_unless(static::autoDraftsEnabled(), LogicException::class, 'Auto drafts are disabled. Set the drafts.auto_drafts.enabled config option to true to use them.');

        $this->newModelQuery()
            ->where($this->getUuidColumn(), $this->{$this->getUuidColumn()})
            ->where($this->getIsAutoColumn(), true)
            ->toBase()
            ->delete();
    }

    /**
     * @param array<string, mixed> ...$attributes
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
            $revisionsToKeep = $this->revisions()->orderByDesc($this->getUpdatedAtColumn() ?? 'updated_at')->onlyDrafts()->withoutCurrent()->withoutAutoDrafts()->take(config('drafts.revisions.keep'))->pluck('id')->merge($this->revisions()->current()->pluck('id'))->merge($this->revisions()->published()->pluck('id'));

            // @phpstan-ignore-next-line method.notFound, method.nonObject
            $this->revisions()->withDrafts()->withoutAutoDrafts()->whereNotIn('id', $revisionsToKeep)->delete();
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

    public function getIsAutoColumn(): string
    {
        return defined(static::class.'::IS_AUTO')
            ? static::IS_AUTO
            : config('drafts.column_names.is_auto', 'is_auto');
    }

    public function getWillPublishAtColumn(): string
    {
        return defined(static::class.'::WILL_PUBLISH_AT')
            ? static::WILL_PUBLISH_AT
            : config('drafts.column_names.will_publish_at', 'will_publish_at');
    }

    /**
     * Whether auto draft support is enabled.
     *
     * Disabled by default so that existing installations without the
     * `is_auto` column keep working; no query references the column until
     * the feature is switched on.
     */
    public static function autoDraftsEnabled(): bool
    {
        return (bool) config('drafts.auto_drafts.enabled', false);
    }

    /**
     * Whether scheduled draft support is enabled.
     *
     * Disabled by default so that existing installations without the
     * `will_publish_at` column keep working; no query references the column
     * until the feature is switched on.
     */
    public static function scheduledDraftsEnabled(): bool
    {
        return (bool) config('drafts.scheduled_drafts.enabled', false);
    }

    public function isCurrent(): bool
    {
        return $this->{$this->getIsCurrentColumn()} ?? false;
    }

    public function isAutoDraft(): bool
    {
        return $this->{$this->getIsAutoColumn()} ?? false;
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
        /** @phpstan-ignore method.nonObject */
        return $this->revisions()->current()->onlyDrafts()->withoutAutoDrafts();
    }

    /**
     * @return HasOne<static, $this>
     */
    public function autoDraft(): HasOne
    {
        return $this->hasOne(static::class, $this->getUuidColumn(), $this->getUuidColumn())->onlyAutoDrafts();
    }

    /**
     * @return MorphTo<Model, TModel>
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
     * @param Builder<TModel> $query
     */
    protected function scopeCurrent(Builder $query): void
    {
        /** @phpstan-ignore method.notFound, method.nonObject */
        $query->withDrafts()->where($this->getIsCurrentColumn(), true);
    }

    /**
     * @param Builder<TModel> $query
     */
    protected function scopeWithoutCurrent(Builder $query): void
    {
        $query->where($this->getIsCurrentColumn(), false);
    }

    /**
     * @param Builder<TModel> $query
     */
    protected function scopeOnlyAutoDrafts(Builder $query): void
    {
        throw_unless(static::autoDraftsEnabled(), LogicException::class, 'Auto drafts are disabled. Set the drafts.auto_drafts.enabled config option to true to use them.');

        /** @phpstan-ignore method.notFound, method.nonObject */
        $query->withDrafts()->where($this->getIsAutoColumn(), true);
    }

    /**
     * @param Builder<TModel> $query
     */
    protected function scopeWithoutAutoDrafts(Builder $query): void
    {
        if (! static::autoDraftsEnabled()) {
            return;
        }

        $query->where($this->getIsAutoColumn(), false);
    }

    /**
     * @param Builder<TModel> $query
     */
    protected function scopeExcludeRevision(Builder $query, int | Model $exclude): void
    {
        $query->where($this->getKeyName(), '!=', is_int($exclude) ? $exclude : $exclude->getKey());
    }

    /**
     * @deprecated This doesn't actually work, will be removed in next version
     * @param Builder<TModel> $query
     */
    protected function scopeWithoutSelf(Builder $query): void
    {
        /** @phpstan-ignore argument.type */
        $query->where('id', '!=', $this->id);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */
    protected function getDraftAttribute(): ?self
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
