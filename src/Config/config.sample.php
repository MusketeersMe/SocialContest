<?php
ini_set('date.timezone', 'America/New_York');

// application configuration using plain php arrays to define settings
return [
    'charset' => 'UTF-8',
    'page' => [
        'title' => 'Social Contest App',
        'app_title' => 'Social Contest',
    ],
    'hashtags' => [
        'myhashtag'
    ],
    // Put your default congratulatory reply on Twitter here.
    // You will be able to customize it before sending.
    'congratulations_text' => "Congratulations! You're the latest winner!",

    /*
     * Decide whether the app should pick winners for you
     *
     * Put contest start date & time and end date & time in local timezone (specified above).
     * Also specify how often winners will be selected. If once, set for the duration of the
     * contest.
     *
     * Update the start end end times for when during the day to run the contest. If 24-hour,
     * set from 00:00 to 23:59
     */
    'contest' => [
        'pick_automatically'    => false,
        'start_date'            => '2013-10-04 11:00', // 24-hour format
        'end_date'              => '2013-10-04 17:31',
        'daily_start'           => '11:00', // 24-hour format
        'daily_end'             => '18:00', // 24-hour format
        'interval'              => '0:1:00' // days:hours:minutes
    ],
    // php5.5 output of password_hash()
    'admin_password' => 'mypasswordhash',

    // azure services
    'azure' => [
        'service_bus' => [
            'namespace' => 'mynamespace',
            'issuer' => 'owner',
            'key' => 'myServiceBusKey',
            'message_topic' => 'messagetopic',
            'message_subscription' => 'wc-sub',
            'to_approved_queue' => 'to-approve',
            'to_incoming_queue' => 'to-incoming',
            'to_denied_queue' => 'to-denied',
            'to_winner_queue' => 'to-winner',
        ],
        'storage' => [
            'name' => 'mystorage', /* for storage account *.core.windows.net */
            'protocol' => 'https', /* http or https */
            'key' => 'mystoragekey',
            'entry_table' => 'entries',
            'image_container' => 'images',
        ],
//        'mssql' => [
//            'host' => 'address.database.windows.net',
//            'db' => 'socon',
//            'user' => 'mydbuser',
//            'password' => 'mydbpassword',
//        ]
    ]
];