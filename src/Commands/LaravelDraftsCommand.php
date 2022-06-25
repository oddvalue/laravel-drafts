<?php

namespace Oddvalue\LaravelDrafts\Commands;

use Illuminate\Console\Command;

class LaravelDraftsCommand extends Command
{
    public $signature = 'laravel-drafts';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
