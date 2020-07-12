<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'token' => env('CRONSKI_TOKEN'),
    'project' => env('CRONSKI_PROJECT'),
    'url' => env('CRONSKI_URL', 'https://cronski.com'),

    // Rather than sending the request straight away, this can be enabled to store the data in the db and send the
    // Request data in batches. This is recommended for sites that have lots of jobs/commands.
    'scheduled' => env('CRONSKI_SCHEDULED', false),

    'commands' => [
        'enabled' => true, // Can be false to not handle commands at all.
        // Eg. app:my-command, app:*.
        // You can use either "excluded" or "included" not both.
        'excluded' => [
            // List of commands to be excluded. All other commands will be included.
        ],
        'included' => [
            // List of command to be included. All other commands will be excluded.
        ],
    ],

    'jobs' => [
        'enabled' => true, // Can be false to not handle jobs at all.
        // Eg. App\Jobs\MyJob, App\Jobs\*.
        // You can use either "excluded" or "included" not both.
        'excluded' => [
            // List of commands to be excluded. All other commands will be included.
        ],
        'included' => [
            // List of command to be included. All other commands will be excluded.
        ],
    ],
];
