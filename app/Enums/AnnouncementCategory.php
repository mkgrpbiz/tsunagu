<?php

namespace App\Enums;

enum AnnouncementCategory: string
{
    case Important = 'important';
    case ProjectInfo = 'project_info';

    public function label(): string
    {
        return match ($this) {
            self::Important => '重要なお知らせ',
            self::ProjectInfo => '案件情報',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Important => 'bg-red-50 text-red-700 border-red-200',
            self::ProjectInfo => 'bg-blue-50 text-blue-700 border-blue-200',
        };
    }
}
