<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['line_uid', 'display_name', 'is_friend', 'followed_at', 'unfollowed_at'])]
class LineUser extends Model
{
    protected function casts(): array
    {
        return [
            'is_friend' => 'boolean',
            'followed_at' => 'datetime',
            'unfollowed_at' => 'datetime',
        ];
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }
}
