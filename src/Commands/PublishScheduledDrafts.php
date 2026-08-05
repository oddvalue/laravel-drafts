<?php

namespace Oddvalue\LaravelDrafts\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Oddvalue\LaravelDrafts\Contracts\Draftable;

class PublishScheduledDrafts extends Command
{
    protected $signature = 'drafts:publish {model}';

    protected $description = 'Publish scheduled drafts';

    public function handle(): int
    {
        $class = $this->argument('model');

        if (! is_string($class)) {
            throw new InvalidArgumentException('The model argument must be a class name.');
        }

        if (
            ! class_exists($class)
            || (
                ! is_subclass_of($class, Draftable::class)
                && ! in_array(HasDrafts::class, class_uses_recursive($class), true)
            )
        ) {
            throw new InvalidArgumentException("The model `{$class}` either doesn't exist, or doesn't implement the `Draftable` contract or use the `HasDrafts` trait.");
        }

        if (! $class::scheduledDraftsEnabled()) {
            throw new InvalidArgumentException('Scheduled drafts are disabled. Set the drafts.scheduled_drafts.enabled config option to true to use them.');
        }

        /** @var Model $model */
        $model = new $class();

        $model->newQuery()
            /** @phpstan-ignore method.notFound */
            ->onlyDrafts()
            /** @phpstan-ignore method.nonObject, method.notFound */
            ->where($model->getWillPublishAtColumn(), '<=', now())
            /** @phpstan-ignore method.nonObject, method.notFound */
            ->whereNull($model->getPublishedAtColumn())
            /** @phpstan-ignore method.nonObject */
            ->each(function (Model $record): void {
                /** @phpstan-ignore method.notFound */
                $record->publish();
                $record->save();
            });

        return Command::SUCCESS;
    }
}
