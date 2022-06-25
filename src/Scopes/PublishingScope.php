<?php

namespace Oddvalue\LaravelDrafts\Scopes;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PublishingScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var string[]
     */
    protected $extensions = ['Publish', 'Unpublish', 'Schedule', 'WithDrafts', 'WithoutDrafts', 'OnlyDrafts'];

    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereDate($model->getQualifiedPublishedAtColumn(), '<=', now());
    }

    public function extend(Builder $builder): void
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    protected function getPublishedAtColumn(Builder $builder): string
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedPublishedAtColumn();
        }

        return $builder->getModel()->getPublishedAtColumn();
    }

    protected function addPublish(Builder $builder): void
    {
        $builder->macro('publish', function (Builder $builder) {
            $builder->withDrafts();

            return $builder->update([$builder->getModel()->getPublishedAtColumn() => now()]);
        });
    }

    protected function addUnpublish(Builder $builder): void
    {
        $builder->macro('unpublish', function (Builder $builder) {
            return $builder->update([$builder->getModel()->getPublishedAtColumn() => null]);
        });
    }

    protected function addSchedule(Builder $builder): void
    {
        $builder->macro('schedule', function (Builder $builder, string | \DateTimeInterface $date) {
            $builder->withDrafts();

            return $builder->update([$builder->getModel()->getPublishedAtColumn() => $date]);
        });
    }

    protected function addWithDrafts(Builder $builder): void
    {
        $builder->macro('withDrafts', function (Builder $builder, $withDrafts = true) {
            if (! $withDrafts) {
                return $builder->withoutDrafts();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    protected function addWithoutDrafts(Builder $builder): void
    {
        $builder->macro('withoutDrafts', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->whereDate($model->getQualifiedPublishedAtColumn(), '<=', now());

            return $builder;
        });
    }

    protected function addOnlyDrafts(Builder $builder): void
    {
        $builder->macro('onlyDrafts', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNull(
                $model->getQualifiedPublishedAtColumn()
            );

            return $builder;
        });
    }
}
