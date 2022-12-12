<?php

namespace TechnologyAdvice\LaravelDrafts;

use Illuminate\Database\Schema\Blueprint;
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
            $publisherMorphName ??= config('drafts.column_names.publisher_morph_name', 'publisher_morph_name');
            $this->dropColumn([
                $uuid ?? config('drafts.column_names.uuid', 'uuid'),
                $publishedAt ?? config('drafts.column_names.published_at', 'published_at'),
                $isPublished ?? config('drafts.column_names.is_published', 'is_published'),
                $isCurrent ?? config('drafts.column_names.is_current', 'is_current'),
                $publisherMorphName.'_id',
                $publisherMorphName.'_type',
            ]);
        });

        $this->app->singleton('laravel-drafts', function () {
            return new LaravelDrafts();
        });
    }
}
