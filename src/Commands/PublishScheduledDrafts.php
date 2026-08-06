<?php

namespace Oddvalue\LaravelDrafts\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;

class PublishScheduledDrafts extends Command
{
    protected $signature = 'drafts:publish {model} {--chunk=1000 : Number of records to fetch per batch}';

    protected $description = 'Publish scheduled drafts';

    public function handle(): int
    {
        $class = $this->argument('model');

        throw_unless(is_string($class), InvalidArgumentException::class, 'The model argument must be a class name.');

        throw_if(! class_exists($class)
        || ! is_subclass_of($class, Model::class)
        || ! in_array(HasDrafts::class, class_uses_recursive($class), true), InvalidArgumentException::class, sprintf("The model `%s` either doesn't exist or isn't an Eloquent model using the `HasDrafts` trait.", $class));

        /** @phpstan-ignore staticMethod.notFound */
        throw_unless((bool) $class::scheduledDraftsEnabled(), InvalidArgumentException::class, 'Scheduled drafts are disabled. Set the drafts.scheduled_drafts.enabled config option to true to use them.');

        $model = new $class();

        $model->newQuery()
            /** @phpstan-ignore method.notFound */
            ->onlyDrafts()
            /** @phpstan-ignore method.nonObject, method.notFound */
            ->where($model->getWillPublishAtColumn(), '<=', now())
            /** @phpstan-ignore method.nonObject, method.notFound */
            ->whereNull($model->getPublishedAtColumn())
            /** @phpstan-ignore method.nonObject */
            ->eachById(function (Model $record): void {
                /** @phpstan-ignore method.notFound */
                $record->publish();
                $record->save();
            }, (int) $this->option('chunk'));

        return Command::SUCCESS;
    }
}
