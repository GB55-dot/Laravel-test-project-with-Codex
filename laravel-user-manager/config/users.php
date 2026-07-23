<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User directory
    |--------------------------------------------------------------------------
    |
    | Values that may differ between environments are read from .env here.
    | Application code reads this configuration instead of calling env()
    | directly, which keeps `php artisan config:cache` safe for production.
    |
    */
    'cache_ttl' => (int) env('USERS_CACHE_TTL', 60),
    'default_per_page' => (int) env('USERS_PER_PAGE', 10),
    'max_per_page' => 100,
];
