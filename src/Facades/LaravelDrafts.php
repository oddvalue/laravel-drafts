<?php

namespace Oddvalue\LaravelDrafts\Facades;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Contracts\Auth\Authenticatable getCurrentUser()
 * @method static void previewMode(bool $previewMode = true)
 * @method static void disablePreviewMode()
 * @method static bool isPreviewModeEnabled()
 * @method static void withDrafts(bool $withDrafts = true)
 * @method static bool isWithDraftsEnabled()
 *
 * @see \Oddvalue\LaravelDrafts\LaravelDrafts
 * @method Model | Authenticatable getCurrentUser();
 */
class LaravelDrafts extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Oddvalue\LaravelDrafts\LaravelDrafts::class;
    }
}
