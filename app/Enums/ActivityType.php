<?php

namespace App\Enums;

enum ActivityType: string
{
    case Individual = 'individual';
    case SoleProprietor = 'sole_proprietor';
    case Corporation = 'corporation';

    public function label(): string
    {
        return match ($this) {
            self::Individual => '個人',
            self::SoleProprietor => '個人事業主',
            self::Corporation => '法人',
        };
    }
}
