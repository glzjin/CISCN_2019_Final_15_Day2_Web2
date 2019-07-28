<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    |
    | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
    | to accept any value.
    |
    */

    'supportsCredentials' => true,
    'allowedOrigins' => ['http://localhost:8080'],
    'allowedOriginsPatterns' => [],
    'allowedHeaders' => ['X-Client-Key', 'X-Requested-With', 'Content-Type'],
    'allowedMethods' => ['*'],
    'exposedHeaders' => ['X-Server-Key'],
    'maxAge' => 0,

];
