<?php

return [
    'informix' => [
        'driver'          => 'informix',
        'host'            => env('DB_IFX_HOST', '127.0.0.1'),
        'database'        => env('DB_IFX_DATABASE', 'sysadmin'),
        'username'        => env('DB_IFX_USERNAME', 'informix'),
        'password'        => env('DB_IFX_PASSWORD', ''),
        'service'         => env('DB_IFX_SERVICE', '11143'),
        'server'          => env('DB_IFX_SERVER', 'ol_informix'),
        'db_locale'       => 'en_US.819',
        'client_locale'   => 'en_US.819',
        'db_encoding'     => 'GBK',
        'initSqls'        => false,
        'enable_scroll'   => 1,
        'protocol'        => 'onsoctcp',
        'client_encoding' => 'UTF-8',
        'prefix'          => '',
    ],

    'informix-source-json' => [
        'driver'  => 'informix-json',
        'source'  => 'source',
        'uri'     => env('DB_IFX_URI', 'http://exmaple.org/json'),
        'token'   => env('DB_IFX_TOKEN', 'SDL3490FI2902309DSFK203SDKL2334202'),
    ],
];
