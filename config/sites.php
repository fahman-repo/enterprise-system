<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Codes
    |--------------------------------------------------------------------------
    |
    | Sites created without an explicit code receive one generated from
    | this prefix and padded sequence (e.g. SIT-00001).
    |
    */

    'site_code_prefix' => env('SITE_CODE_PREFIX', 'SIT'),

    'site_code_padding' => (int) env('SITE_CODE_PADDING', 5),

];
