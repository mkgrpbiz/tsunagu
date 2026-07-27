<?php

namespace App\Models;

use App\Enums\AnnouncementCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['body', 'category', 'is_draft', 'notify_line', 'line_message'])]
class Announcement extends Model
{
    protected function casts(): array
    {
        return [
            'category' => AnnouncementCategory::class,
            'is_draft' => 'boolean',
            'notify_line' => 'boolean',
        ];
    }
}
