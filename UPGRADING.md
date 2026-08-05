<!-- markdownlint-disable MD043 -->
# Upgrade guide

## Enabling scheduled drafts on an existing installation

Scheduled drafts are opt-in and disabled by default. Tables created with
`$table->drafts()` after this feature was introduced already include the
`will_publish_at` column; tables created before it need the column added
before the feature is enabled:

```php
Schema::table('posts', function (Blueprint $table): void {
    $table->timestamp('will_publish_at')->nullable();
});
```

Then enable the feature in `config/drafts.php`:

```php
'scheduled_drafts' => [
    'enabled' => true,
],
```

## Enabling auto drafts on an existing installation

Auto drafts follow the same pattern and require the `is_auto` column:

```php
Schema::table('posts', function (Blueprint $table): void {
    $table->boolean('is_auto')->default(false);
});
```

Then enable the feature in `config/drafts.php`:

```php
'auto_drafts' => [
    'enabled' => true,
],
```

## The `Draftable` contract

The `Oddvalue\LaravelDrafts\Contracts\Draftable` contract describes the
consumer-facing draftable API and is satisfied by the `HasDrafts` trait.
Implementing it is currently optional, but recommended:

```php
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Oddvalue\LaravelDrafts\Contracts\Draftable;

class Post extends Model implements Draftable
{
    use HasDrafts;
}
```

A future major version will require draftable models to implement the
contract. Models that only use the trait keep working until then; add the
interface at your leisure. If you have many models, Rector can add it for
you with the built-in `AddInterfaceByTraitRector` rule:

```php
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Oddvalue\LaravelDrafts\Contracts\Draftable;
use Rector\Config\RectorConfig;
use Rector\Transform\Rector\Class_\AddInterfaceByTraitRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/app/Models'])
    ->withConfiguredRule(AddInterfaceByTraitRector::class, [
        HasDrafts::class => Draftable::class,
    ]);
```

The rule is idempotent and appends to any existing `implements` list. Note
that it also adds the interface to child classes that inherit the trait
from a parent; that is redundant but harmless.
