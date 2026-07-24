<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case InternalProcessing = 'internal_processing';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => '未払い',
            self::Paid => '支払済み',
            self::InternalProcessing => '社内処理',
        };
    }
}
