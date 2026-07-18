<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'contract_id',
    'referrer_agency_id',
    'source_agency_id',
    'amount',
    'payment_due_date',
    'payment_status',
    'paid_at',
])]
class ReferralCommission extends Model
{
    protected function casts(): array
    {
        return [
            'payment_due_date' => 'date',
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function referrerAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'referrer_agency_id');
    }

    public function sourceAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'source_agency_id');
    }
}
