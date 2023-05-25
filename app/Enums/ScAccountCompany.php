<?php

namespace App\Enums;

enum ScAccountCompany: int
{
    case GPC = 2;

    public function name(): string
    {
        return match($this) {
            self::GPC => 'GPC',
        };
    }
}
