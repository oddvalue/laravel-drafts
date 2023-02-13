<?php

namespace Oddvalue\LaravelDrafts\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Oddvalue\LaravelDrafts\Contacts\Draftable;

class PublishScheduledDrafts extends Command
{
    protected $signature = 'drafts:publish {model}';

    protected $description = 'Published scheduled drafts';

    public function handle(): int
    {
        $class = $this->argument('model');

        if (! class_exists($class) || ! in_array(Draftable::class, class_implements($class), strict: true)) {
            throw new InvalidArgumentException("The model `{$class}` either doesn't exist or doesn't implement `Draftable`.");
        }

        $model = new $class();

        $model::query()
            ->onlyDrafts()
            ->where($model->getWillPublishAtColumn(), '<', now())
            ->whereNull($model->getPublishedAtColumn())
            ->each(function (Draftable $record): void {
                $record->setLive();
                $record->save();
            });

        return Command::SUCCESS;
    }
}
