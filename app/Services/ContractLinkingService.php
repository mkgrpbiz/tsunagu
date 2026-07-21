<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Enums\PaymentStatus;
use App\Models\Contract;
use App\Models\Inquiry;
use App\Models\ReferralCommission;
use Illuminate\Support\Carbon;

class ContractLinkingService
{
    /**
     * @param  array<int, array{tsunagu_unit_price: int, agency_unit_price: int, count: int, apply_referral_commission?: bool}>  $lines
     */
    public function linkInquiry(Inquiry $inquiry, array $lines): bool
    {
        if ($inquiry->contract && ! $inquiry->project->is_recurring) {
            return false;
        }

        $depositDate = Carbon::now();
        $paymentDueDate = $depositDate->copy()->addMonthNoOverflow()->day(5);

        foreach ($lines as $line) {
            $contract = Contract::create([
                'inquiry_id' => $inquiry->id,
                'deposit_date' => $depositDate,
                'deposit_amount' => $line['tsunagu_unit_price'] * $line['count'],
                'agency_reward_amount' => $line['agency_unit_price'] * $line['count'],
                'payment_due_date' => $paymentDueDate,
                'payment_status' => PaymentStatus::Unpaid,
            ]);

            $applyReferralCommission = (bool) ($line['apply_referral_commission'] ?? true);

            if ($applyReferralCommission && $inquiry->agency->referred_by_agency_id) {
                ReferralCommission::create([
                    'contract_id' => $contract->id,
                    'referrer_agency_id' => $inquiry->agency->referred_by_agency_id,
                    'source_agency_id' => $inquiry->agency_id,
                    'amount' => (int) round($contract->agency_reward_amount * 0.1),
                    'payment_due_date' => $paymentDueDate,
                    'payment_status' => PaymentStatus::Unpaid,
                ]);
            }
        }

        $inquiry->update(['status' => InquiryStatus::Contracted]);

        return true;
    }
}
