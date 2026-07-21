<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'inquiry_id',
    'deposit_date',
    'deposit_amount',
    'agency_reward_amount',
    'payment_due_date',
    'payment_status',
    'paid_at',
])]
class Contract extends Model
{
    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
            'payment_due_date' => 'date',
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'date',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function referralCommission(): HasOne
    {
        return $this->hasOne(ReferralCommission::class);
    }
}
