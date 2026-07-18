<?php

namespace App\Enums;

enum AgencyStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '審査中',
            self::Approved => '承認済み',
            self::Rejected => '否認',
            self::Suspended => '利用停止',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-50 text-yellow-700 border-yellow-200',
            self::Approved => 'bg-green-50 text-green-700 border-green-200',
            self::Rejected => 'bg-red-50 text-red-700 border-red-200',
            self::Suspended => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }
}
