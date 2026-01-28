## Laravel Drafts

A simple, drop-in drafts/revisions system for Laravel Eloquent models.

### Features

- Drafts and revisions for any Eloquent model via a single trait.
- Schema helper macros to add or remove required columns.
- Query scopes for published, drafts, and current revisions.
- Preview mode and middleware for draft access.

### Installation

@verbatim
<code-snippet name="Install package" lang="bash">
composer require oddvalue/laravel-drafts
php artisan vendor:publish --tag="drafts-config"
</code-snippet>
@endverbatim

### Required model setup

- Add the `HasDrafts` trait to the model.
- Ensure the model table has the draft columns (use the schema macros).
- Optionally define `$draftableRelations` if you want relations copied/synced on publish.

@verbatim
<code-snippet name="Model setup" lang="php">
use Illuminate\Database\Eloquent\Model;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;

class Post extends Model
{
    use HasDrafts;

    protected array $draftableRelations = [
        'tags',
    ];
}
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Migration helpers" lang="php">
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('posts', function (Blueprint $table) {
    $table->drafts();
});
</code-snippet>
@endverbatim

### Creating drafts and publishing

- New records are published by default.
- Use `createDraft`, `saveAsDraft`, or `updateAsDraft` to keep the published record unchanged.

@verbatim
<code-snippet name="Create or update a draft" lang="php">
Post::createDraft(['title' => 'Draft title']);

$post = Post::create(['title' => 'Live title']);
$post->updateAsDraft(['title' => 'Draft edit']);
</code-snippet>
@endverbatim

### Querying revisions

@verbatim
<code-snippet name="Scopes" lang="php">
$published = Post::withoutDrafts()->get();
$withDrafts = Post::withDrafts()->get();
$draftsOnly = Post::onlyDrafts()->get();
$current = Post::current()->get();
</code-snippet>
@endverbatim

### Preview mode and middleware

@verbatim
<code-snippet name="Preview mode" lang="php">
use Oddvalue\LaravelDrafts\Facades\LaravelDrafts;

LaravelDrafts::previewMode();
LaravelDrafts::disablePreviewMode();
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="WithDraftsMiddleware" lang="php">
use Oddvalue\LaravelDrafts\Http\Middleware\WithDraftsMiddleware;

Route::withDrafts(function (): void {
    Route::get('/posts/publish/{post}', [PostController::class, 'publish']);
});
</code-snippet>
@endverbatim
