<?php

namespace App\Enums;

enum LegalDocumentType: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';
    case PartnerAgreement = 'partner_agreement';

    public function label(): string
    {
        return match ($this) {
            self::Terms => '利用規約',
            self::Privacy => 'プライバシーポリシー',
            self::PartnerAgreement => 'パートナー業務委託契約書',
        };
    }
}
