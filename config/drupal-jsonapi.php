<?php

return [
    /**
     * The base URL for all endpoints defined by the JSON API in the Drupal installation.
     * e.g. https://cms.mydomain.com/jsonapi
     */
    'base_url' => env('DRUPAL_JSON_API_URL'),

    /**
     * Allow models and collections to be cached for the time define.
     */
    'use_cache' => env('DRUPAL_JSON_API_CACHE', false),

    /**
     * The timezone used for storing dates in the Drupal install's database.
     * Normally this is UTC by default and shouldn't be changed.
     */
    'timezone' => 'UTC',
];
