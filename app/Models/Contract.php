<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'inquiry_id',
    'project_id',
    'deposit_date',
    'deposit_amount',
    'agency_reward_amount',
    'agency_unit_price',
    'count',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 着金紐付け画面の「変更」で個別に別案件を指定した場合はそちらを、
     * 未指定なら問い合わせ自身の案件を「実際の案件」として使う。
     */
    public function effectiveProject(): ?Project
    {
        return $this->project ?? $this->inquiry->project;
    }

    public function referralCommission(): HasOne
    {
        return $this->hasOne(ReferralCommission::class);
    }

    public function sharePoyDepositRecord(): HasOne
    {
        return $this->hasOne(SharePoyDepositRecord::class);
    }
}
