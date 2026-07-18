<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Published = 'published';
    case Paused = 'paused';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Published => '公開中',
            self::Paused => '停止中',
            self::Closed => '終了',
        };
    }
}
