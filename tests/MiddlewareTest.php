<?php

use Illuminate\Support\Facades\Route;
use Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware;
use Oddvalue\LaravelDrafts\Tests\Post;

beforeEach(function () {
    Post::create(['title' => 'Hello World']);
    Post::createDraft(['title' => 'Hello World draft']);

    Route::middleware(['web'])->group(function () {
        Route::get('/default', function () {
            return Post::all();
        });

        Route::get('/with-drafts-middleware', function () {
            return Post::all();
        })->middleware(WithDraftsMiddleware::class);

        Route::withDrafts(fn () => Route::get('/with-drafts-macro', function () {
            return Post::all();
        }));
    });
});

it('can use with drsft middleware to include drafts on a route', function () {
    $this->get('/with-drafts-middleware')->assertJsonCount(2);
});

it('can use with drsft macro to include drafts on a route', function () {
    $this->get('/with-drafts-macro')->assertJsonCount(2);
});

it('doesnt include drafts by default', function () {
    $this->get('/default')->assertJsonCount(1);
});
