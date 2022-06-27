<?php
// config for Oddvalue/LaravelDrafts
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
