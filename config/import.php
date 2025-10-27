
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Student Import Settings
    |--------------------------------------------------------------------------
    |
    | Configure how students are imported and their credentials are generated.
    |
    */

    // When true: username = phone, password = phone (fast, less secure)
    // When false: generate random credentials and send via WhatsApp (secure, slower)
    'use_phone_as_credentials' => env('IMPORT_USE_PHONE_AS_CREDENTIALS', true),

    // Send WhatsApp credentials only when using random credentials
    'send_credentials_via_whatsapp' => env('IMPORT_SEND_CREDENTIALS_WHATSAPP', false),
];