<?php

namespace App\Enums;

enum CollaborationPartnerApplicationStatus: string
{
    case Pending = 'pending';
    case Handled = 'handled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未対応',
            self::Handled => '対応済',
        };
    }
}
