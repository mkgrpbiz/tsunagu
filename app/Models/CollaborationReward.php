<?php

namespace App\Models;

use App\Enums\CollaborationRewardStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_name', 'month', 'reward_amount', 'status', 'payment_status', 'payment_due_date', 'paid_at'])]
class CollaborationReward extends Model
{
    protected function casts(): array
    {
        return [
            'month' => 'date',
            'status' => CollaborationRewardStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }
}
