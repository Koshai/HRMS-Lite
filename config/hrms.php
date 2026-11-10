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

    /*
    |--------------------------------------------------------------------------
    | Attendance
    |--------------------------------------------------------------------------
    |
    | Maximum open-to-close duration for a single attendance record. Overnight
    | check-out is allowed when it stays within this window and after check-in.
    |
    */

    'attendance_max_hours' => (int) env('HRMS_ATTENDANCE_MAX_HOURS', 16),

];
