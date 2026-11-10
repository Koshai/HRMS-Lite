<?php

namespace App\Enums;

enum Role: string
{
    case SuperUser = 'super_user';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::SuperUser => 'Super user',
            self::Employee => 'Employee',
        };
    }
}
