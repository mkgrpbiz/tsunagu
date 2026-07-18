<?php

namespace App\Enums;

enum BankAccountType: string
{
    case Ordinary = 'ordinary';
    case Checking = 'checking';

    public function label(): string
    {
        return match ($this) {
            self::Ordinary => '普通',
            self::Checking => '当座',
        };
    }
}
