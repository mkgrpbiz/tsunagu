<?php

namespace App\Enums;

enum CollaborationPartnerApplicationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '審査中',
            self::Approved => '承認済',
            self::Rejected => '見送り',
        };
    }
}
