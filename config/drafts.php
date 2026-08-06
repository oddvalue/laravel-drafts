<?php

declare(strict_types=1);

// config for Oddvalue/LaravelDrafts
return [
    'revisions' => [
        'keep' => 10,
    ],

    'auto_drafts' => [
        /*
         * Whether auto draft support is enabled. Auto drafts require the
         * `is_auto` column, so installations upgrading from a version without
         * it must add the column to their drafted tables before enabling:
         * $table->boolean('is_auto')->default(false);
         */
        'enabled' => false,
    ],

    'scheduled_drafts' => [
        /*
         * Whether scheduled draft support is enabled. Scheduled drafts require
         * the `will_publish_at` column, so installations upgrading from a
         * version without it must add the column to their drafted tables
         * before enabling:
         * $table->timestamp('will_publish_at')->nullable();
         */
        'enabled' => false,
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
         * Boolean column that marks a row as an auto draft: an auto-saved
         * working copy that is updated in place and never published.
         */
        'is_auto' => 'is_auto',

        /*
         * Timestamp column that stores the date and time when the row was published.
         */
        'published_at' => 'published_at',

        /*
         * Timestamp column that stores the date and time when the row is scheduled for publishing.
         */
        'will_publish_at' => 'will_publish_at',

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
