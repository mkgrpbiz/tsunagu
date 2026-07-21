<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'agency_id',
    'project_id',
    'invite_link_id',
    'line_user_id',
    'name',
    'name_kana',
    'email',
    'status',
    'guidance_sent_at',
    'inquired_at',
    'is_legacy_import',
    'legacy_line_display_name',
])]
class Inquiry extends Model
{
    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
            'guidance_sent_at' => 'datetime',
            'inquired_at' => 'datetime',
            'is_legacy_import' => 'boolean',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function inviteLink(): BelongsTo
    {
        return $this->belongsTo(InviteLink::class);
    }

    public function lineUser(): BelongsTo
    {
        return $this->belongsTo(LineUser::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class)->latestOfMany();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
