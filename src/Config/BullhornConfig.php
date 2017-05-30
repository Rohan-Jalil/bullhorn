<?php
/**
 * Created by PhpStorm.
 * Date: 5/19/17
 * Time: 8:26 PM
 */

namespace NorthCreek\Bullhorn\Config\BullhornConfig;

return [

    'credentials_storage_path' => base_path()."/storage/app/credentials.json",

    'oauth' => [

      'storage' => '\OAuth\Common\Storage\Session',

      'http_client' => '\OAuth\Common\Http\Client\CurlClient',

      'credentials' => '\OAuth\Common\Consumer\Credentials',

    ],

    'timezone' => [

        'date' => 'America/Edmonton'
    ],

    'bullhorn' => [

        'username' => 'brixproject.api',
        'password' => '67GSTA?45xR/Eq='

    ]


];
