<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Enums\AgencyStatus;
use App\Enums\BankAccountType;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'referred_by_agency_id',
    'name',
    'name_kana',
    'gender',
    'prefecture',
    'occupation',
    'phone',
    'email',
    'password',
    'bank_name',
    'bank_branch_name',
    'bank_account_type',
    'bank_account_number',
    'bank_account_holder',
    'must_change_password',
    'status',
    'review_note',
    'approved_at',
    'approved_by_user_id',
    'last_login_at',
    'activity_type',
    'company_name',
    'desired_activities',
    'current_activity',
    'track_record',
    'media_urls',
    'self_pr',
    'is_collaboration_partner',
    'collaboration_partner_at',
])]
#[Hidden(['password', 'remember_token'])]
class Agency extends Authenticatable
{
    use HasFactory, Notifiable;

    public const DESIRED_ACTIVITY_OPTIONS = [
        '案件紹介',
        '営業代行',
        'パートナー活動',
        '共創・業務提携',
        '自社案件掲載',
        'その他',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'bank_account_type' => BankAccountType::class,
            'must_change_password' => 'boolean',
            'password' => 'hashed',
            'status' => AgencyStatus::class,
            'approved_at' => 'datetime',
            'last_login_at' => 'datetime',
            'activity_type' => ActivityType::class,
            'desired_activities' => 'array',
            'is_collaboration_partner' => 'boolean',
            'collaboration_partner_at' => 'datetime',
        ];
    }

    public function inviteLinks(): HasMany
    {
        return $this->hasMany(InviteLink::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function contracts(): HasManyThrough
    {
        return $this->hasManyThrough(Contract::class, Inquiry::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_agency_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_agency_id');
    }

    public function referralCommissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class, 'referrer_agency_id');
    }

    public function collaborationReferrals(): HasMany
    {
        return $this->hasMany(CollaborationReferral::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'referrer_agency_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(AgencyStatusHistory::class);
    }

    public function legalDocumentConsents(): HasMany
    {
        return $this->hasMany(LegalDocumentConsent::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function getReferralCodeAttribute(): string
    {
        return sprintf('B%04d', $this->id);
    }

    protected static function booted(): void
    {
        static::updating(function (self $agency) {
            if ($agency->isDirty('password')) {
                \Illuminate\Support\Facades\Log::info('Agency password changing', [
                    'agency_id' => $agency->id,
                    'old_hash' => $agency->getOriginal('password'),
                    'new_hash' => $agency->getAttribute('password'),
                    'trace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15))
                        ->map(fn ($t) => ($t['class'] ?? '').($t['type'] ?? '').($t['function'] ?? '').':'.($t['line'] ?? ''))
                        ->implode(' <- '),
                ]);
            }
        });
    }
}
