<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case GuidanceFailed = 'guidance_failed';
    case Guided = 'guided';
    case Contracted = 'contracted';

    public function label(): string
    {
        return match ($this) {
            self::New => '案内待ち',
            self::GuidanceFailed => 'エラー',
            self::Guided => '案内済',
            self::Contracted => '着金',
        };
    }
}
