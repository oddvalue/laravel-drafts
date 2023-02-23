<?php

namespace Oddvalue\LaravelDrafts\Facades;

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
 */
class LaravelDrafts extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Oddvalue\LaravelDrafts\LaravelDrafts::class;
    }
}
