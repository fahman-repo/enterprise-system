<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Entity Codes
    |--------------------------------------------------------------------------
    |
    | Entities created without an explicit code receive one generated from
    | this prefix and padded sequence (e.g. ENT-00001).
    |
    */

    'entity_code_prefix' => env('ENTITY_CODE_PREFIX', 'ENT'),

    'entity_code_padding' => (int) env('ENTITY_CODE_PADDING', 5),

];
