<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Enums\AgencyStatus;
use App\Enums\BankAccountType;
use App\Enums\CollaborationRewardStatus;
use App\Enums\Gender;
use App\Enums\LegalDocumentType;
use App\Enums\PaymentStatus;
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
    'legacy_code',
    'legacy_referral_code',
    'oshigoto_token',
    'name',
    'name_kana',
    'gender',
    'prefecture',
    'occupation',
    'phone',
    'email',
    'password',
    'bank_name',
    'bank_code',
    'bank_branch_name',
    'bank_branch_code',
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
        return $this->legacy_code ?: sprintf('B%04d', $this->id);
    }

    public static function generateUniqueLegacyCode(int $startFrom): string
    {
        $candidate = $startFrom;

        do {
            $code = sprintf('B%04d', $candidate);
            $taken = self::where('legacy_code', $code)->exists();
            $candidate++;
        } while ($taken);

        return $code;
    }

    public function totalPendingPayout(): int
    {
        $contractsTotal = $this->contracts()
            ->where('payment_status', PaymentStatus::Unpaid)
            ->sum('agency_reward_amount');

        $referralTotal = $this->referralCommissions()
            ->where('payment_status', PaymentStatus::Unpaid)
            ->sum('amount');

        $clientNames = $this->projects()->whereNotNull('client_name')->distinct()->pluck('client_name');

        $collaborationTotal = CollaborationReward::whereIn('client_name', $clientNames)
            ->where('status', CollaborationRewardStatus::Approved)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->sum('reward_amount');

        return (int) ($contractsTotal + $referralTotal + $collaborationTotal);
    }

    public static function carryOverSummary(int $threshold = 1000): array
    {
        $collaborationReferrerIds = CollaborationReward::where('status', CollaborationRewardStatus::Approved)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->get()
            ->map(fn (CollaborationReward $reward) => Project::where('client_name', $reward->client_name)
                ->whereNotNull('referrer_agency_id')
                ->value('referrer_agency_id'));

        $agencyIdsWithUnpaid = collect()
            ->merge(
                Contract::where('payment_status', PaymentStatus::Unpaid)->with('inquiry')->get()->pluck('inquiry.agency_id')
            )
            ->merge(
                ReferralCommission::where('payment_status', PaymentStatus::Unpaid)->pluck('referrer_agency_id')
            )
            ->merge($collaborationReferrerIds)
            ->unique()->filter()->values();

        $rows = static::whereIn('id', $agencyIdsWithUnpaid)->get()
            ->map(fn (self $agency) => ['agency' => $agency, 'total' => $agency->totalPendingPayout()])
            ->filter(fn (array $row) => $row['total'] > 0 && $row['total'] < $threshold)
            ->sortByDesc('total')
            ->values();

        return [
            'rows' => $rows,
            'total' => $rows->sum('total'),
        ];
    }

    public function hasBankInfoRegistered(): bool
    {
        return filled($this->bank_name)
            && filled($this->bank_branch_name)
            && filled($this->bank_account_number)
            && filled($this->bank_account_holder);
    }

    public function hasSubmittedAllConsents(): bool
    {
        $consentedTypes = $this->legalDocumentConsents()
            ->join('legal_documents', 'legal_documents.id', '=', 'legal_document_consents.legal_document_id')
            ->pluck('legal_documents.type');

        return collect(LegalDocumentType::cases())
            ->every(fn (LegalDocumentType $type) => $consentedTypes->contains($type->value));
    }

    protected static function booted(): void
    {
        static::created(function (self $agency) {
            if (! $agency->legacy_code) {
                $agency->updateQuietly(['legacy_code' => self::generateUniqueLegacyCode($agency->id)]);
            }
        });
    }

    public function getOrCreateOshigotoToken(): string
    {
        if ($this->oshigoto_token) {
            return $this->oshigoto_token;
        }

        do {
            $token = \Illuminate\Support\Str::random(10);
        } while (self::where('oshigoto_token', $token)->exists());

        $this->update(['oshigoto_token' => $token]);

        return $token;
    }
}
