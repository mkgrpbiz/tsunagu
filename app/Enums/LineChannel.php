<?php

namespace App\Enums;

enum LineChannel: string
{
    case Partner = 'partner';
    case Customer = 'customer';

    public function configKey(): string
    {
        return match ($this) {
            self::Partner => 'services.line_partner',
            self::Customer => 'services.line_customer',
        };
    }
}
