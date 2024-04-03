![](https://banners.beyondco.de/Laravel%20Drafts.png?theme=dark&packageManager=composer+require&packageName=oddvalue%2Flaravel-drafts&pattern=architect&style=style_1&description=A+simple%2C+drop-in+drafts%2Frevisions+system+for+Laravel+models&md=1&showWatermark=1&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg "Laravel Drafts")



# A simple, drop-in drafts/revisions system for Laravel models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/oddvalue/laravel-drafts.svg?style=flat-square)](https://packagist.org/packages/oddvalue/laravel-drafts)
![PHP Support](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Foddvalue%2Flaravel-drafts%2Fmain%2Fcomposer.json&query=require.php&label=PHP)
![Laravel Support](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Foddvalue%2Flaravel-drafts%2Fmain%2Fcomposer.json&query=require%5B'illuminate%2Fcontracts'%5D&label=Laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/oddvalue/laravel-drafts/run-tests.yml?label=tests&style=flat-square)](https://github.com/oddvalue/laravel-drafts/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/oddvalue/laravel-drafts/php-cs-fixer.yml?label=code%20style&style=flat-square)](https://github.com/oddvalue/laravel-drafts/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/oddvalue/laravel-drafts.svg?style=flat-square)](https://packagist.org/packages/oddvalue/laravel-drafts)
![Coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/oddvalue/9dd8e508cb2433728d42a258193770eb/raw/laravel-drafts-cobertura-coverage.json)

* [Installation](#installation)
* [Usage](#usage)
  + [Preparing your models](#preparing-your-models)
    - [Add the trait](#add-the-trait)
    - [Relations](#relations)
    - [Database](#database)
  + [The API](#the-api)
    - [Creating a new record](#creating-a-new-record)
    - [Relations](#relations)
  + [Interacting with records](#interacting-with-records)
    - [Published revision](#published-revision)
    - [Current Revision](#current-revision)
    - [Revisions](#revisions)
    - [Preview mode](#preview-mode)
  + [Middleware](#middleware)
    - [WithDraftsMiddleware](#withdraftsmiddleware)
* [Testing](#testing)
* [Changelog](#changelog)
* [Contributing](#contributing)
* [Security Vulnerabilities](#security-vulnerabilities)
* [Credits](#credits)
* [License](#license)

## Installation

You can install the package via composer:

```bash
composer require oddvalue/laravel-drafts
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="drafts-config"
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

#### Add the trait

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

#### Relations

The package can handle basic relations to other models. When a draft is published `HasOne` and `HasMany` relations will be duplicated to the published model and `BelongsToMany` and `MorphToMany` relations will be synced to the published model. In order for this to happen you first need to set the `$draftableRelations` property on the model.

```php
protected array $draftableRelations = [
    'posts',
    'tags',
];
```

Alternatively you may override the `getDraftableRelations` method.

```php
public function getDraftableRelations()
{
    return ['posts', 'tags'];
}
```

#### Database 

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

### Interacting with records

#### Published revision

The published revision if the live version of the record and will be the one that is displayed to the public. The default behavior is to only show the published revision.

```php
# Get all published posts
$posts = Post::all();
```

#### Current Revision

Every record will have a current revision. That is the most recent revision and what you would want to display in your admin. 

To fetch the current revision you can call the `current` scope.

```php
$posts = Post::current()->get();
```

#### Revisions

Every time a record is updated a new row/revision will be inserted. The default number of revisions kept is 10, this can be updated in the published config file.

You can fetch the revisions of a record by calling the `revisions` method.

```php
$post = Post::find(1);
$revisions = $post->revisions();
```

Deleting a record will also delete all of its revisions. Soft deleting records will soft delete the revisions and restoring records will restore the revisions.

If you need to update a record without creating revision

```php
$post->withoutRevision()->update($options);
```

#### Preview Mode

Enabling preview mode will disable the global scope that fetches only published records and will instead fetch the current revision regardless of published state.

```php
# Enable preview mode
\Oddvalue\LaravelDrafts\Facades\LaravelDrafts::previewMode();
\Oddvalue\LaravelDrafts\Facades\LaravelDrafts::previewMode(true);

# Disable preview mode
\Oddvalue\LaravelDrafts\Facades\LaravelDrafts::disablePreviewMode();
\Oddvalue\LaravelDrafts\Facades\LaravelDrafts::previewMode(false);
```

### Middleware

#### WithDraftsMiddleware

If you require a specific route to be able to access drafts then you can use the `WithDraftsMiddleware` middleware.

```php
Route::get('/posts/publish/{post}', [PostController::class, 'publish'])->middleware(\Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware::class);
```

There is also a helper method on the router that allows you to create a group with that middleware applied.

```php
Route::withDrafts(function (): void {
    Route::get('/posts/publish/{post}', [PostController::class, 'publish']);
});
```

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
