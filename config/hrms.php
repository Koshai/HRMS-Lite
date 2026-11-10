<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Installation defaults
    |--------------------------------------------------------------------------
    |
    | HRMS Lite is one business per installation. These defaults are Bangladesh-
    | first and can be overridden per environment without multi-tenancy.
    |
    */

    'display_timezone' => env('HRMS_DISPLAY_TIMEZONE', 'Asia/Dhaka'),

    'currency' => env('HRMS_CURRENCY', 'BDT'),

];
