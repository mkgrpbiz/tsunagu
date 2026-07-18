<?php

namespace App\Models;

use App\Enums\CollaborationRewardStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_name', 'month', 'reward_amount', 'status'])]
class CollaborationReward extends Model
{
    protected function casts(): array
    {
        return [
            'month' => 'date',
            'status' => CollaborationRewardStatus::class,
        ];
    }
}
