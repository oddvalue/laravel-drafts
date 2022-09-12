<?php

namespace TechnologyAdvice\LaravelDrafts\Facades;

use Illuminate\Support\Facades\Facade;
use TechnologyAdvice\LaravelDrafts\LaravelDrafts as LaravelDraftsLaravelDrafts;

/**
 * @see \TechnologyAdvice\LaravelDrafts\LaravelDrafts
 */
class LaravelDrafts extends Facade
{
    protected static function getFacadeAccessor()
    {
        return LaravelDraftsLaravelDrafts::class;
    }
}
