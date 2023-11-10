<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Wrapper configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for Invoice Wrapper like:
    | - selected Provider
    | - available Providers
    | - providers configuration
    |
    */

    'selected_provider' => env('INVOICING_PROVIDER'),
    'providers' => [
        'billingo' => [
            'name' => 'Billingo',
            'base_url' => env('BILLINGO_BASE_URL', 'https://api.billingo.hu/v3/'),
            'api_key' => env('BILLINGO_API_KEY'),
            'block_id' => env('BILLINGO_BLOCK_ID', 0),
        ],
        'szamlazzhu' => [
            'name' => 'Szamlazz.hu',
            'api_key' => env('SZAMLAZZHU_API_KEY'),
        ]
    ],
];
