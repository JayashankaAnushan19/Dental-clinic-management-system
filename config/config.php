<?php
/**
 * Application configuration.
 * Change values here - no other file needs editing.
 */
return [
    'app_name' => 'SmileCare Dental',
    'tagline'  => 'Modern dental care for the whole family',

    // URL path where the app is served (must match the htdocs link name in start.bat)
    'base_url' => '/dentalclinic',

    'timezone' => 'Asia/Colombo',
    'currency' => 'Rs.',

    // Length of one booking slot in minutes
    'slot_minutes' => 30,
    // How many days ahead patients may book
    'booking_days_ahead' => 60,

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'dentalclinic_v2',
        'user' => 'admin',
        'pass' => 'admin',
    ],

    // Show PHP errors in the browser (turn off for a real deployment)
    'debug' => true,
];
