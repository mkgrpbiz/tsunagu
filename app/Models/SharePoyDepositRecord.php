<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sharepoy_user_id',
    'inquiry_id',
    'source',
    'deposit_date',
    'tsunagu_unit_price',
    'agency_unit_price',
    'count',
    'memo',
])]
class SharePoyDepositRecord extends Model
{
    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
        ];
    }

    public function sharePoyUser(): BelongsTo
    {
        return $this->belongsTo(SharePoyUser::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }
}
