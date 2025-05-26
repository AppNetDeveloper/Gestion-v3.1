<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Aquí puedes definir los proxies de confianza para tu aplicación. 
    | Útil cuando estás detrás de un balanceador de carga, API Gateway, etc.
    |
    | Para confiar en todos los proxies, usa "*"
    | Para confiar solo en proxies específicos, lista sus direcciones IP
    |
    */

    'proxies' => env('TRUSTED_PROXIES', null),

    /*
    |--------------------------------------------------------------------------
    | Proxy Headers
    |--------------------------------------------------------------------------
    |
    | Aquí puedes definir los encabezados HTTP que se deben usar para detectar
    | información del proxy.
    |
    */

    'headers' => [
        (defined('Illuminate\Http\Request::HEADER_X_FORWARDED_FOR') ? Illuminate\Http\Request::HEADER_X_FORWARDED_FOR : 'X_FORWARDED_FOR'),
        (defined('Illuminate\Http\Request::HEADER_X_FORWARDED_HOST') ? Illuminate\Http\Request::HEADER_X_FORWARDED_HOST : 'X_FORWARDED_HOST'),
        (defined('Illuminate\Http\Request::HEADER_X_FORWARDED_PORT') ? Illuminate\Http\Request::HEADER_X_FORWARDED_PORT : 'X_FORWARDED_PORT'),
        (defined('Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO') ? Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO : 'X_FORWARDED_PROTO'),
        (defined('Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB') ? Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB : 'X_FORWARDED_AWS_ELB'),
    ],
];
