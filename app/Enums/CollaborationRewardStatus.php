<?php

namespace App\Enums;

enum CollaborationRewardStatus: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => '承認待ち',
            self::Approved => '承認',
        };
    }
}
