<p style="background: darkgoldenrod; padding: 1em; font-weight: bold; font-size: large">
    This package is a work in progress. It is not yet ready for production use.
</p>

# A simple, drop-in drafts/revisions system for Laravel models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/oddvalue/laravel-drafts.svg?style=flat-square)](https://packagist.org/packages/oddvalue/laravel-drafts)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/oddvalue/laravel-drafts/run-tests?label=tests)](https://github.com/oddvalue/laravel-drafts/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/oddvalue/laravel-drafts/Check%20&%20fix%20styling?label=code%20style)](https://github.com/oddvalue/laravel-drafts/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/oddvalue/laravel-drafts.svg?style=flat-square)](https://packagist.org/packages/oddvalue/laravel-drafts)

## Installation

You can install the package via composer:

```bash
composer require oddvalue/laravel-drafts
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-drafts-config"
```

This is the contents of the published config file:

```php
return [
    'revisions' => [
        'keep' => 10,
    ],

    'column_names' => [
        /*
         * Boolean column that marks a row as the current version of the data for editing.
         */
        'is_current' => 'is_current',

        /*
         * Boolean column that marks a row as live and displayable to the public.
         */
        'is_published' => 'is_published',

        /*
         * Timestamp column that stores the date and time when the row was published.
         */
        'published_at' => 'published_at',

        /*
         * UUID column that stores the unique identifier of the model drafts.
         */
        'uuid' => 'uuid',

        /*
         * Name of the morph relationship to the publishing user.
         */
        'publisher_morph_name' => 'publisher',
    ],

    'auth' => [
        /*
         * The guard to fetch the logged-in user from for the publisher relation.
         */
        'guard' => 'web',
    ],
];
```

## Usage

### Preparing your models

Add the `HasDrafts` trait to your model

```php
<?php

use Illuminate\Database\Eloquent\Model;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;

class Post extends Model
{
    use HasDrafts;
    
    ...
}
```

### Database 

The following database columns are required for the model to store drafts and revisions:

* is_current
* is_published
* published_at
* uuid
* publisher_type
* publisher_id

The names of these columns can be changed in the config file or per model using constants 

e.g. To alter the name of the `is_current` column then you would add a class constant called `IS_CURRENT`

```php
<?php

use Illuminate\Database\Eloquent\Model;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;

class Post extends Model
{
    use HasDrafts;
    
    public const IS_CURRENT = 'admin_editing';
    
    ...
}
```

There are two helper methods added to the schema builder for use in your migrations that will add/remove all these columns for you:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
Schema::table('posts', function (Blueprint $table) {
    $table->drafts();
});
 
Schema::table('posts', function (Blueprint $table) {
    $table->dropDrafts();
});
```

### The API

The `HasDrafts` trait will add a default scope that will only return published/live records.

The following quiery builder methods are available to alter this behavior:

* `withoutDrafts()`/`published(bool $withoutDrafts = true)` Only select published records (default)
* `withDrafts(bool $withDrafts = false)` Include draft record
* `onlyDrafts()` Select only drafts, exclude published

#### Creating a new record

By default, new records will be created as published. You can change this either by including `'is_published' => false` in the attributes of the model or by using the `createDraft` or `saveAsDraft` methods.

```php
Post::create([
    'title' => 'Foo',
    'is_published' => false,
]);

# OR

Post::createDraft(['title' => 'Foo']);

# OR

Post::make(['title' => 'Foo'])->saveAsDraft();
```

When saving/updating a record the published state will be maintained. If you want to save a draft of a published record then you can use the `saveAsDraft` and `updateAsDraft` methods.

```php
# Create published post
$post = Post::create(['title' => 'Foo']);

# Create drafted copy

$post->updateAsDraft(['title' => 'Bar']);

# OR

$post->title = 'Bar';
$post->saveAsDraft(); 
```

This will create a draft record and the original record will be left unchanged. 

| # | title | uuid                                 | published_at        | is_published | is_current | created_at          | updated_at          |
|---|-------|--------------------------------------|---------------------|--------------|------------|---------------------|---------------------|
| 1 | Foo   | 9188eb5b-cc42-47e9-aec3-d396666b4e80 | 2000-01-01 00:00:00 | 1            | 0          | 2000-01-01 00:00:00 | 2000-01-01 00:00:00 |
| 2 | Bar   | 9188eb5b-cc42-47e9-aec3-d396666b4e80 | 2000-01-02 00:00:00 | 0            | 1          | 2000-01-02 00:00:00 | 2000-01-02 00:00:00 |

#### Revisions

Every time a record is updated a new row/revision will be inserted. The default number of revisions kept is 10, this can be updated in the published config file.  

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [jim](https://github.com/oddvalue)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
