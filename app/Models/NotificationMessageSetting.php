<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['feature', 'approved_message', 'rejected_message'])]
class NotificationMessageSetting extends Model
{
    public const FEATURE_COLLABORATION_REFERRAL = 'collaboration_referral';

    public const FEATURE_COLLABORATION_PARTNER_APPLICATION = 'collaboration_partner_application';

    public static function forFeature(string $feature, string $defaultApprovedMessage, string $defaultRejectedMessage): self
    {
        return static::firstOrCreate(
            ['feature' => $feature],
            ['approved_message' => $defaultApprovedMessage, 'rejected_message' => $defaultRejectedMessage],
        );
    }
}
