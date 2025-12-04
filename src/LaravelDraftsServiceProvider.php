<?php

namespace Oddvalue\LaravelDrafts;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
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

    public function packageRegistered(): void
    {
        $this->app->singleton(LaravelDrafts::class, fn (): LaravelDrafts => new LaravelDrafts());

        /** @phpstan-ignore offsetAccess.nonOffsetAccessible, method.nonObject */
        $this->app[Kernel::class]->prependToMiddlewarePriority(WithDraftsMiddleware::class);

        Blueprint::macro('drafts', function (
            ?string $uuid = null,
            ?string $publishedAt = null,
            ?string $isPublished = null,
            ?string $isCurrent = null,
            ?string $publisherMorphName = null,
        ): void {
            /** @var string $uuidCol */
            $uuidCol = $uuid ?? config('drafts.column_names.uuid', 'uuid');
            /** @var string $publishedAtCol */
            $publishedAtCol = $publishedAt ?? config('drafts.column_names.published_at', 'published_at');
            /** @var string $isPublishedCol */
            $isPublishedCol = $isPublished ?? config('drafts.column_names.is_published', 'is_published');
            /** @var string $isCurrentCol */
            $isCurrentCol = $isCurrent ?? config('drafts.column_names.is_current', 'is_current');
            /** @var string $publisherMorphNameCol */
            $publisherMorphNameCol = $publisherMorphName ?? config('drafts.column_names.publisher_morph_name', 'publisher_morph_name');

            $this->uuid($uuidCol)->nullable();
            $this->timestamp($publishedAtCol)->nullable();
            $this->boolean($isPublishedCol)->default(false);
            $this->boolean($isCurrentCol)->default(false);
            $this->nullableMorphs($publisherMorphNameCol);

            $this->index([$uuidCol, $isPublishedCol, $isCurrentCol]);
        });

        Blueprint::macro('dropDrafts', function (
            ?string $uuid = null,
            ?string $publishedAt = null,
            ?string $isPublished = null,
            ?string $isCurrent = null,
            ?string $publisherMorphName = null,
        ): void {
            /** @var string $uuidCol */
            $uuidCol = $uuid ?? config('drafts.column_names.uuid', 'uuid');
            /** @var string $publishedAtCol */
            $publishedAtCol = $publishedAt ?? config('drafts.column_names.published_at', 'published_at');
            /** @var string $isPublishedCol */
            $isPublishedCol = $isPublished ?? config('drafts.column_names.is_published', 'is_published');
            /** @var string $isCurrentCol */
            $isCurrentCol = $isCurrent ?? config('drafts.column_names.is_current', 'is_current');
            /** @var string $publisherMorphNameCol */
            $publisherMorphNameCol = $publisherMorphName ?? config('drafts.column_names.publisher_morph_name', 'publisher_morph_name');

            $this->dropIndex([$uuidCol, $isPublishedCol, $isCurrentCol]);
            $this->dropMorphs($publisherMorphNameCol);

            $this->dropColumn([
                $uuidCol,
                $publishedAtCol,
                $isPublishedCol,
                $isCurrentCol,
            ]);
        });

        Route::macro('withDrafts', function (\Closure $routes): void {
            Route::middleware(WithDraftsMiddleware::class)->group($routes);
        });
    }
}
