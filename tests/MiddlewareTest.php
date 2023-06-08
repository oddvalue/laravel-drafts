<?php

use Illuminate\Support\Facades\Route;
use Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware;
use Oddvalue\LaravelDrafts\Tests\Post;

use function Pest\Laravel\get;

beforeEach(function () {
    test()->post = Post::create(['title' => 'Hello World']);
    test()->draftPost = Post::createDraft(['title' => 'Hello World draft']);

    Route::middleware(['web'])->group(function () {
        Route::get('/default', function () {
            return Post::all();
        });

        Route::get('/with-drafts-middleware', function () {
            return Post::all();
        })->middleware(WithDraftsMiddleware::class);

        Route::get('/with-drafts-middleware/{post}', function (Post $post) {
            return $post;
        })->middleware(WithDraftsMiddleware::class);

        Route::withDrafts(function () {
            Route::get('/with-drafts-macro', function () {
                return Post::all();
            });
            Route::get('/with-drafts-macro/{post}', function (Post $post) {
                return $post;
            });
        });
    });
});

it('can use with draft middleware to include drafts on a route', function () {
    get('/with-drafts-middleware')->assertJsonCount(2);
});

it('can use with draft macro to include drafts on a route', function () {
    get('/with-drafts-macro')->assertJsonCount(2);
});

it('doesnt include drafts by default', function () {
    get('/default')->assertJsonCount(1);
});

it('can use with draft middleware to include drafts on a model binding', function () {
    get('/with-drafts-middleware/' . test()->draftPost->id)
        ->assertJsonFragment(['title' => 'Hello World draft']);
});

it('can use with draft macro to include drafts on a model binding', function () {
    get('/with-drafts-macro/' . test()->draftPost->id)
        ->assertJsonFragment(['title' => 'Hello World draft']);
});
