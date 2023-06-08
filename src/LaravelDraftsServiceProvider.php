<?php

namespace Oddvalue\LaravelDrafts;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelDraftsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-drafts')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageRegistered()
    {
        if (method_exists($this->app['db']->connection()->getSchemaBuilder(), 'useNativeSchemaOperationsIfPossible')) {
            Schema::useNativeSchemaOperationsIfPossible();
        }

        $this->app->singleton(LaravelDrafts::class, function () {
            return new LaravelDrafts();
        });

        $this->app[Kernel::class]->prependToMiddlewarePriority(WithDraftsMiddleware::class);

        Blueprint::macro('drafts', function (
            string $uuid = null,
            string $publishedAt = null,
            string $isPublished = null,
            string $isCurrent = null,
            string $publisherMorphName = null,
        ) {
            /** @var Blueprint $this */
            $uuid ??= config('drafts.column_names.uuid', 'uuid');
            $publishedAt ??= config('drafts.column_names.published_at', 'published_at');
            $isPublished ??= config('drafts.column_names.is_published', 'is_published');
            $isCurrent ??= config('drafts.column_names.is_current', 'is_current');
            $publisherMorphName ??= config('drafts.column_names.publisher_morph_name', 'publisher_morph_name');

            $this->uuid($uuid)->nullable();
            $this->timestamp($publishedAt)->nullable();
            $this->boolean($isPublished)->default(false);
            $this->boolean($isCurrent)->default(false);
            $this->nullableMorphs($publisherMorphName);

            $this->index([$uuid, $isPublished, $isCurrent]);
        });

        Blueprint::macro('dropDrafts', function (
            string $uuid = null,
            string $publishedAt = null,
            string $isPublished = null,
            string $isCurrent = null,
            string $publisherMorphName = null,
        ) {
            /** @var Blueprint $this */
            $uuid ??= config('drafts.column_names.uuid', 'uuid');
            $publishedAt ??= config('drafts.column_names.published_at', 'published_at');
            $isPublished ??= config('drafts.column_names.is_published', 'is_published');
            $isCurrent ??= config('drafts.column_names.is_current', 'is_current');
            $publisherMorphName ??= config('drafts.column_names.publisher_morph_name', 'publisher_morph_name');

            $this->dropIndex([$uuid, $isPublished, $isCurrent]);
            $this->dropMorphs($publisherMorphName);

            $this->dropColumn([
                $uuid,
                $publishedAt,
                $isPublished,
                $isCurrent,
            ]);
        });

        Route::macro('withDrafts', function (\Closure $routes): void {
            Route::middleware(WithDraftsMiddleware::class)->group($routes);
        });
    }
}
