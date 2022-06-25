<?php

namespace Oddvalue\LaravelDrafts\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Oddvalue\LaravelDrafts\LaravelDrafts
 */
class LaravelDrafts extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-drafts';
    }
}
