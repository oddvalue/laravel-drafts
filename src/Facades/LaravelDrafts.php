<?php

namespace TechnologyAdvice\LaravelDrafts\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \TechnologyAdvice\LaravelDrafts\LaravelDrafts
 */
class LaravelDrafts extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-drafts';
    }
}
