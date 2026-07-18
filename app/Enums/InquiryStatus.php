<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Guided = 'guided';
    case Contracted = 'contracted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => '新規',
            self::Guided => '案内済',
            self::Contracted => '着金',
            self::Lost => '失注',
        };
    }
}
