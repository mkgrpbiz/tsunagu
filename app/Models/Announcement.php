<?php

namespace App\Models;

use App\Enums\AnnouncementCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['body', 'category', 'notify_line', 'line_message'])]
class Announcement extends Model
{
    protected function casts(): array
    {
        return [
            'category' => AnnouncementCategory::class,
            'notify_line' => 'boolean',
        ];
    }
}
