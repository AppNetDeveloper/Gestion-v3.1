<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the default configuration for invoices.
    |
    */
    'irpf' => env('INVOICE_IRPF', 0), // Porcentaje de retención de IRPF para autónomos
    
    /*
    |--------------------------------------------------------------------------
    | Bank Account Information
    |--------------------------------------------------------------------------
    */
    'bank' => [
        'name' => env('BANK_NAME', 'Bank Name'),
        'account_holder' => env('BANK_ACCOUNT_HOLDER', 'Your Company Name'),
        'account_number' => env('BANK_ACCOUNT_NUMBER', 'ES00 0000 0000 0000 0000 0000'),
        'swift_bic' => env('BANK_SWIFT_BIC', 'SWIFTBIC'),
    ],
];
