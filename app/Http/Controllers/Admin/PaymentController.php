<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollaborationRewardStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\CollaborationReward;
use App\Models\Contract;
use App\Models\NotificationMessageSetting;
use App\Models\Project;
use App\Models\ReferralCommission;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private const CARRY_OVER_THRESHOLD = 1000;

    private const DEFAULT_PAYMENT_MESSAGE = 'お振込みが完了しました。金額: {amount}円';

    public function index(Request $request): View
    {
        $allContracts = Contract::with(['inquiry.project', 'inquiry.agency'])
            ->orderBy('payment_due_date')
            ->get();

        $allCommissions = ReferralCommission::with(['referrerAgency', 'sourceAgency'])
            ->orderBy('payment_due_date')
            ->get();

        $clientReferrers = Project::whereNotNull('client_name')
            ->whereNotNull('referrer_agency_id')
            ->with('referrerAgency')
            ->get()
            ->keyBy('client_name');

        $allCollaborationRewards = CollaborationReward::where('status', CollaborationRewardStatus::Approved)
            ->orderBy('payment_due_date')
            ->get()
            ->map(function (CollaborationReward $reward) use ($clientReferrers) {
                $reward->referrerAgency = $clientReferrers->get($reward->client_name)?->referrerAgency;

                return $reward;
            })
            ->filter(fn (CollaborationReward $reward) => $reward->referrerAgency !== null)
            ->values();

        // 現在未払い残高を持つ全パートナー（月の絞り込みに関係なく「今の状態」で判定する）
        $agencyIdsWithUnpaid = collect()
            ->merge($allContracts->where('payment_status', PaymentStatus::Unpaid)->map(fn (Contract $c) => $c->inquiry->agency_id))
            ->merge($allCommissions->where('payment_status', PaymentStatus::Unpaid)->pluck('referrer_agency_id'))
            ->merge($allCollaborationRewards->where('payment_status', PaymentStatus::Unpaid)->map(fn (CollaborationReward $r) => $r->referrerAgency->id))
            ->unique()->filter()->values();

        $agencies = Agency::whereIn('id', $agencyIdsWithUnpaid)->get()->keyBy('id');
        $payableAgencyIds = $agencies->filter(fn (Agency $a) => $a->totalPendingPayout() >= self::CARRY_OVER_THRESHOLD)->keys();

        ['rows' => $carryOverAgencies, 'total' => $carryOverTotal] = Agency::carryOverSummary(self::CARRY_OVER_THRESHOLD);

        $months = $allContracts->map(fn (Contract $contract) => $contract->payment_due_date->format('Y-m'))->toBase()
            ->merge($allCommissions->map(fn (ReferralCommission $commission) => $commission->payment_due_date->format('Y-m')))
            ->merge($allCollaborationRewards->map(fn (CollaborationReward $reward) => $reward->payment_due_date->format('Y-m')))
            ->unique()->sortDesc()->values();

        $month = $request->query('month', $months->first());
        $month = $month === 'all' ? null : $month;

        $monthContracts = $allContracts->when($month, fn ($collection) => $collection->filter(
            fn (Contract $contract) => $contract->payment_due_date->format('Y-m') === $month
        ));

        $monthCommissions = $allCommissions->when($month, fn ($collection) => $collection->filter(
            fn (ReferralCommission $commission) => $commission->payment_due_date->format('Y-m') === $month
        ));

        $monthCollaborationRewards = $allCollaborationRewards->when($month, fn ($collection) => $collection->filter(
            fn (CollaborationReward $reward) => $reward->payment_due_date->format('Y-m') === $month
        ));

        $isPayable = fn (?int $agencyId, PaymentStatus $status) => $status === PaymentStatus::Paid || $payableAgencyIds->contains($agencyId);

        $payableContracts = $monthContracts->filter(
            fn (Contract $contract) => $isPayable($contract->inquiry->agency_id, $contract->payment_status)
        )->values();

        $payableCommissions = $monthCommissions->filter(
            fn (ReferralCommission $commission) => $isPayable($commission->referrer_agency_id, $commission->payment_status)
        )->values();

        $payableCollaborationRewards = $monthCollaborationRewards->filter(
            fn (CollaborationReward $reward) => $isPayable($reward->referrerAgency->id, $reward->payment_status)
        )->values();

        $monthlyTotal = $payableContracts->where('payment_status', PaymentStatus::Unpaid)->sum('agency_reward_amount')
            + $payableCommissions->where('payment_status', PaymentStatus::Unpaid)->sum('amount')
            + $payableCollaborationRewards->where('payment_status', PaymentStatus::Unpaid)->sum('reward_amount');

        $cumulativeTotal = $allContracts->where('payment_status', PaymentStatus::Unpaid)->sum('agency_reward_amount')
            + $allCommissions->where('payment_status', PaymentStatus::Unpaid)->sum('amount')
            + $allCollaborationRewards->where('payment_status', PaymentStatus::Unpaid)->sum('reward_amount');

        return view('admin.payments.index', [
            'contractsByAgency' => $payableContracts->groupBy(fn (Contract $contract) => $contract->inquiry->agency->name),
            'referralCommissionsByAgency' => $payableCommissions->groupBy(fn (ReferralCommission $commission) => $commission->referrerAgency->name),
            'collaborationRewardsByAgency' => $payableCollaborationRewards->groupBy(fn (CollaborationReward $reward) => $reward->referrerAgency->name),
            'carryOverAgencies' => $carryOverAgencies,
            'carryOverTotal' => $carryOverTotal,
            'months' => $months,
            'month' => $month,
            'monthlyTotal' => $monthlyTotal,
            'cumulativeTotal' => $cumulativeTotal,
        ]);
    }

    public function update(Contract $contract, LineMessagingService $lineMessaging): RedirectResponse
    {
        $contract->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->notifyPaymentCompleted($contract->inquiry->agency, (int) $contract->agency_reward_amount, $lineMessaging);

        return redirect()->route('admin.payments.index')->with('status', '支払済みにしました。');
    }

    public function revert(Contract $contract): RedirectResponse
    {
        $contract->update([
            'payment_status' => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);

        return redirect()->route('admin.payments.index')->with('status', '未払いに戻しました。');
    }

    public function updateReferralCommission(ReferralCommission $referralCommission, LineMessagingService $lineMessaging): RedirectResponse
    {
        $referralCommission->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->notifyPaymentCompleted($referralCommission->referrerAgency, (int) $referralCommission->amount, $lineMessaging);

        return redirect()->route('admin.payments.index')->with('status', 'パートナー10%を支払済みにしました。');
    }

    public function revertReferralCommission(ReferralCommission $referralCommission): RedirectResponse
    {
        $referralCommission->update([
            'payment_status' => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);

        return redirect()->route('admin.payments.index')->with('status', 'パートナー10%を未払いに戻しました。');
    }

    public function updateCollaborationReward(CollaborationReward $collaborationReward, LineMessagingService $lineMessaging): RedirectResponse
    {
        $collaborationReward->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $referrerAgency = Project::where('client_name', $collaborationReward->client_name)
            ->whereNotNull('referrer_agency_id')
            ->with('referrerAgency')
            ->first()?->referrerAgency;

        $this->notifyPaymentCompleted($referrerAgency, (int) $collaborationReward->reward_amount, $lineMessaging);

        return redirect()->route('admin.payments.index')->with('status', '共創パートナー30%を支払済みにしました。');
    }

    public function revertCollaborationReward(CollaborationReward $collaborationReward): RedirectResponse
    {
        $collaborationReward->update([
            'payment_status' => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);

        return redirect()->route('admin.payments.index')->with('status', '共創パートナー30%を未払いに戻しました。');
    }

    private function notifyPaymentCompleted(?Agency $agency, int $amount, LineMessagingService $lineMessaging): void
    {
        if (! $agency || ! $agency->line_uid || ! $agency->line_notify_payment) {
            return;
        }

        $setting = NotificationMessageSetting::forFeature(
            NotificationMessageSetting::FEATURE_PAYMENT_COMPLETED,
            self::DEFAULT_PAYMENT_MESSAGE,
            '',
        );

        $message = str_replace('{amount}', number_format($amount), $setting->approved_message);

        $lineMessaging->sendPush($agency->line_uid, $message);
    }
}
