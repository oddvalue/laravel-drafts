<?php


use Oddvalue\LaravelDrafts\Tests\Post;

it('can override columns via config', function () {
    config([
        'drafts.column_names' => [
            'published_at' => 'published_at_override',
            'is_published' => 'is_published_override',
            'is_current' => 'is_current_override',
            'uuid' => 'uuid_override',
            'publisher_morph_name' => 'publisher_override',
        ],
    ]);
    $post = Post::make();

    expect($post->getPublishedAtColumn())->toBe('published_at_override')
        ->and($post->getIsPublishedColumn())->toBe('is_published_override')
        ->and($post->getIsCurrentColumn())->toBe('is_current_override')
        ->and($post->getUuidColumn())->toBe('uuid_override')
        ->and($post->getPublisherColumns())->toBe(['id' => 'publisher_override_id', 'type' => 'publisher_override_type']);
});

it('can override columns via class constants', function () {
    $post = new class () extends Post {
        public const PUBLISHED_AT = 'published_at_override';
        public const WILL_PUBLISH_AT = 'will_publish_at_overridee';
        public const IS_PUBLISHED = 'is_published_override';
        public const IS_CURRENT = 'is_current_override';
        public const UUID = 'uuid_override';
        public const PUBLISHER_ID = 'publisher_override_id';
        public const PUBLISHER_TYPE = 'publisher_override_type';
    };

    expect($post->getPublishedAtColumn())->toBe($post::PUBLISHED_AT)
        ->and($post->getQualifiedPublishedAtColumn())->toBe($post->qualifyColumn($post::PUBLISHED_AT))
        ->and($post->getWillPublishAtColumn())->toBe($post::WILL_PUBLISH_AT)
        ->and($post->getIsPublishedColumn())->toBe($post::IS_PUBLISHED)
        ->and($post->getIsCurrentColumn())->toBe($post::IS_CURRENT)
        ->and($post->getUuidColumn())->toBe($post::UUID)
        ->and($post->getPublisherColumns())->toBe(['id' => $post::PUBLISHER_ID, 'type' => $post::PUBLISHER_TYPE])
        ->and($post->getQualifiedPublisherColumns())->toBe($post->qualifyColumns(['id' => $post::PUBLISHER_ID, 'type' => $post::PUBLISHER_TYPE]));
});
