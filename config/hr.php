<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Employee Numbers
    |--------------------------------------------------------------------------
    |
    | Employees created without an explicit employee number receive one
    | generated from this prefix and padded sequence (e.g. EMP-00001).
    |
    */

    'employee_number_prefix' => env('HR_EMPLOYEE_NUMBER_PREFIX', 'EMP'),

    'employee_number_padding' => (int) env('HR_EMPLOYEE_NUMBER_PADDING', 5),

    'development_program_code_prefix' => env('HR_DEVELOPMENT_PROGRAM_CODE_PREFIX', 'DEV'),

    'development_program_code_padding' => (int) env('HR_DEVELOPMENT_PROGRAM_CODE_PADDING', 5),

];
