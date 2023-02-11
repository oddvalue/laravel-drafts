<?php

namespace Oddvalue\LaravelDrafts\Facades;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Oddvalue\LaravelDrafts\LaravelDrafts
 * @method Model | Authenticatable getCurrentUser();
 */
class LaravelDrafts extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-drafts';
    }
}
