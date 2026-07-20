<?php

namespace App\Models;

use App\Enums\CollaborationPartnerApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'agency_id',
    'collaboration_content',
    'proposal_details',
    'expected_role',
    'reference_url',
    'status',
])]
class CollaborationPartnerApplication extends Model
{
    protected function casts(): array
    {
        return [
            'status' => CollaborationPartnerApplicationStatus::class,
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
