<?php

namespace App\Enums;

enum LegalDocumentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => '下書き',
            self::Published => '公開',
            self::Unpublished => '非公開',
        };
    }
}
