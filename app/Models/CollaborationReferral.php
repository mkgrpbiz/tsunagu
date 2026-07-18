<?php

namespace App\Models;

use App\Enums\CollaborationReferralStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'agency_id',
    'referred_name',
    'referred_company',
    'referred_business',
    'referred_track_record',
    'reason',
    'consent_obtained',
    'status',
])]
class CollaborationReferral extends Model
{
    protected function casts(): array
    {
        return [
            'consent_obtained' => 'boolean',
            'status' => CollaborationReferralStatus::class,
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
