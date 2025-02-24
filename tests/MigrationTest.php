<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->app['db']->connection()->getSchemaBuilder()->create('foo', function (Blueprint $table): void {
        $table->increments('id');
        $table->timestamps();
    });
});

it('adds the required draft columns to the table', function (): void {
    $this->app['db']->connection()->getSchemaBuilder()->table('foo', function (Blueprint $table): void {
        $table->drafts();
    });

    expect(Schema::hasColumns('foo', [
        config('drafts.column_names.uuid'),
        config('drafts.column_names.published_at'),
        config('drafts.column_names.is_published'),
        config('drafts.column_names.is_current'),
        config('drafts.column_names.publisher_morph_name').'_id',
        config('drafts.column_names.publisher_morph_name').'_type',
    ]))->toBeTrue();
});

it('allows column names to be overridden when migrating', function (): void {
    $this->app['db']->connection()->getSchemaBuilder()->table('foo', function (Blueprint $table): void {
        $table->drafts(
            uuid: 'uuid_override',
            publishedAt: 'published_at_override',
            isPublished: 'is_published_override',
            isCurrent: 'is_current_override',
            publisherMorphName: 'publisher_override'
        );
    });

    expect(Schema::hasColumns('foo', [
        'uuid_override',
        'published_at_override',
        'is_published_override',
        'is_current_override',
        'publisher_override_id',
        'publisher_override_type',
    ]))->toBeTrue();
});

it('drops draft columns', function (): void {
    $this->app['db']->connection()->getSchemaBuilder()->table('foo', function (Blueprint $table): void {
        $table->drafts();
    });

    expect(Schema::hasColumns('foo', [
        'uuid',
        'published_at',
        'is_published',
        'is_current',
        'publisher_id',
        'publisher_type',
    ]))->toBeTrue();

    $this->app['db']->connection()->getSchemaBuilder()->table('foo', function (Blueprint $table): void {
        $table->dropDrafts();
    });

    expect(Schema::hasColumns('foo', [
        'uuid',
        'published_at',
        'is_published',
        'is_current',
        'publisher_id',
        'publisher_type',
    ]))->toBeFalse();
});

it('drops custom named draft columns', function (): void {
    $this->app['db']->connection()->getSchemaBuilder()->table('foo', function (Blueprint $table): void {
        $table->drafts(
            uuid: 'uuid_override',
            publishedAt: 'published_at_override',
            isPublished: 'is_published_override',
            isCurrent: 'is_current_override',
            publisherMorphName: 'publisher_override'
        );
    });

    expect(Schema::hasColumns('foo', [
        'uuid_override',
        'published_at_override',
        'is_published_override',
        'is_current_override',
        'publisher_override_id',
        'publisher_override_type',
    ]))->toBeTrue();

    $this->app['db']->connection()->getSchemaBuilder()->table('foo', function (Blueprint $table): void {
        $table->dropDrafts(
            uuid: 'uuid_override',
            publishedAt: 'published_at_override',
            isPublished: 'is_published_override',
            isCurrent: 'is_current_override',
            publisherMorphName: 'publisher_override'
        );
    });

    expect(Schema::hasColumns('foo', [
        'uuid_override',
        'published_at_override',
        'is_published_override',
        'is_current_override',
        'publisher_override_id',
        'publisher_override_type',
    ]))->toBeFalse();
});
