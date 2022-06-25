<?php

namespace Oddvalue\LaravelDrafts;

use Illuminate\Database\Schema\Blueprint;
use Oddvalue\LaravelDrafts\Commands\LaravelDraftsCommand;
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
            ->hasViews()
            ->hasMigration('create_laravel-drafts_table')
            ->hasCommand(LaravelDraftsCommand::class);

        Blueprint::macro('drafts', function () {
            /** @var Blueprint $this */
            $this->uuid(config('drafts.column_names.uuid', 'uuid'))->index();
            $this->timestamp(config('drafts.column_names.published_at', 'published_at'))->nullable();
            $this->boolean(config('drafts.column_names.is_current', 'is_current'))->default(false);
            $this->nullableMorphs(config('drafts.column_names.publisher_morph_name', 'publisher_morph_name'));
        });

        Blueprint::macro('dropDrafts', function () {
            /** @var Blueprint $this */
            $this->dropColumn(config('drafts.column_names.uuid', 'uuid'));
            $this->dropColumn(config('drafts.column_names.published_at', 'published_at'));
            $this->dropColumn(config('drafts.column_names.is_current', 'is_current'));
            $this->dropMorphs(config('drafts.column_names.publisher_morph_name', 'publisher_morph_name'));
        });

        $this->app->singleton('laravel-drafts', function () {
            return new LaravelDrafts();
        });
    }
}
