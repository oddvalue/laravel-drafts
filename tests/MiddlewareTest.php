<?php

use Illuminate\Support\Facades\Route;
use Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware;
use Oddvalue\LaravelDrafts\Tests\app\Models\Post;

use function Pest\Laravel\get;

beforeEach(function (): void {
    test()->post = Post::create(['title' => 'Hello World']);
    test()->draftPost = Post::createDraft(['title' => 'Hello World draft']);

    Route::middleware(['web'])->group(function (): void {
        Route::get('/default', fn () => Post::all());

        Route::get('/with-drafts-middleware', fn () => Post::all())->middleware(WithDraftsMiddleware::class);

        Route::get('/with-drafts-middleware/{post}', fn (Post $post): Post => $post)->middleware(
            WithDraftsMiddleware::class,
        );

        Route::withDrafts(function (): void {
            Route::get('/with-drafts-macro', fn () => Post::all());
            Route::get('/with-drafts-macro/{post}', fn (Post $post): Post => $post);
        });
    });
});

it('can use with draft middleware to include drafts on a route', function (): void {
    get('/with-drafts-middleware')->assertJsonCount(2);
});

it('can use with draft macro to include drafts on a route', function (): void {
    get('/with-drafts-macro')->assertJsonCount(2);
});

it('doesnt include drafts by default', function (): void {
    get('/default')->assertJsonCount(1);
});

it('can use with draft middleware to include drafts on a model binding', function (): void {
    get('/with-drafts-middleware/' . test()->draftPost->id)
        ->assertJsonFragment(['title' => 'Hello World draft']);
});

it('can use with draft macro to include drafts on a model binding', function (): void {
    get('/with-drafts-macro/' . test()->draftPost->id)
        ->assertJsonFragment(['title' => 'Hello World draft']);
});
