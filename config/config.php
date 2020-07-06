<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'token' => env('CRONSKI_TOKEN'),
    'project' => env('CRONSKI_PROJECT'),
    'url' => env('CRONSKI_URL', 'https://cronski.com'),

    'queue' => false, // To add all items to a queue. Might have to specify the queue here too.

    'commands' => [
        'enabled' => true, // Can be false, then will only log command implementing the interface.
        // Eg. app:my-command, app:*.
        // You can use either "excluded" or "included" not both.
        'excluded' => [
            // List of commands to be excluded. All other commands will be included.
        ],
        'included' => [
            // List of command to be included. All other commands will be excluded.
        ],
    ],
];
