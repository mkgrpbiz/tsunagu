<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BankAccountType;
use App\Enums\CollaborationRewardStatus;
use App\Enums\LineChannel;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\CollaborationReward;
use App\Models\Contract;
use App\Models\NotificationMessageSetting;
use App\Models\Project;
use App\Models\ReferralCommission;
use App\Services\LineMessagingService;
use App\Services\ZenginTransferCsvBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

        $summaries = [];

        foreach ($payableContracts as $contract) {
            $agencyId = $contract->inquiry->agency_id;
            $summaries[$agencyId] ??= ['agency' => $contract->inquiry->agency, 'contract_total' => 0, 'commission_total' => 0, 'reward_total' => 0];

            if ($contract->payment_status === PaymentStatus::Unpaid) {
                $summaries[$agencyId]['contract_total'] += $contract->agency_reward_amount;
            }
        }

        foreach ($payableCommissions as $commission) {
            $agencyId = $commission->referrer_agency_id;
            $summaries[$agencyId] ??= ['agency' => $commission->referrerAgency, 'contract_total' => 0, 'commission_total' => 0, 'reward_total' => 0];

            if ($commission->payment_status === PaymentStatus::Unpaid) {
                $summaries[$agencyId]['commission_total'] += $commission->amount;
            }
        }

        foreach ($payableCollaborationRewards as $reward) {
            $agencyId = $reward->referrerAgency->id;
            $summaries[$agencyId] ??= ['agency' => $reward->referrerAgency, 'contract_total' => 0, 'commission_total' => 0, 'reward_total' => 0];

            if ($reward->payment_status === PaymentStatus::Unpaid) {
                $summaries[$agencyId]['reward_total'] += $reward->reward_amount;
            }
        }

        $agencySummaries = collect($summaries)->map(function (array $row) {
            $row['total'] = $row['contract_total'] + $row['commission_total'] + $row['reward_total'];

            return $row;
        })->sortByDesc('total')->values();

        return view('admin.payments.index', [
            'agencySummaries' => $agencySummaries,
            'carryOverAgencies' => $carryOverAgencies,
            'carryOverTotal' => $carryOverTotal,
            'months' => $months,
            'month' => $month,
            'monthlyTotal' => $monthlyTotal,
            'cumulativeTotal' => $cumulativeTotal,
            'defaultTransferDate' => $this->nextTransferDate(),
        ]);
    }

    public function show(Agency $agency): View
    {
        $contracts = $agency->contracts()->with(['inquiry.project'])->orderByDesc('payment_due_date')->get();

        $commissions = $agency->referralCommissions()->with('sourceAgency')->orderByDesc('payment_due_date')->get();

        $clientNames = $agency->projects()->whereNotNull('client_name')->distinct()->pluck('client_name');

        $collaborationRewards = CollaborationReward::whereIn('client_name', $clientNames)
            ->where('status', CollaborationRewardStatus::Approved)
            ->orderByDesc('payment_due_date')
            ->get();

        $unpaidTotal = $contracts->where('payment_status', PaymentStatus::Unpaid)->sum('agency_reward_amount')
            + $commissions->where('payment_status', PaymentStatus::Unpaid)->sum('amount')
            + $collaborationRewards->where('payment_status', PaymentStatus::Unpaid)->sum('reward_amount');

        $paidTotal = $contracts->where('payment_status', PaymentStatus::Paid)->sum('agency_reward_amount')
            + $commissions->where('payment_status', PaymentStatus::Paid)->sum('amount')
            + $collaborationRewards->where('payment_status', PaymentStatus::Paid)->sum('reward_amount');

        return view('admin.payments.show', [
            'agency' => $agency,
            'contracts' => $contracts,
            'commissions' => $commissions,
            'collaborationRewards' => $collaborationRewards,
            'unpaidTotal' => $unpaidTotal,
            'paidTotal' => $paidTotal,
        ]);
    }

    public function payAll(Agency $agency, LineMessagingService $lineMessaging): RedirectResponse
    {
        $total = $this->markAgencyPaid($agency, $lineMessaging);

        if ($total <= 0) {
            return redirect()->route('admin.payments.show', $agency)->with('status', '未払いの項目がありませんでした。');
        }

        return redirect()->route('admin.payments.show', $agency)->with('status', 'まとめて支払済みにしました。');
    }

    public function payAllAgencies(LineMessagingService $lineMessaging): RedirectResponse
    {
        $rows = $this->payableAgencySummaries();
        $paidCount = 0;

        foreach ($rows as $row) {
            if ($this->markAgencyPaid($row['agency'], $lineMessaging) > 0) {
                $paidCount++;
            }
        }

        return redirect()->route('admin.payments.index')->with('status', "{$paidCount}件のパートナーをまとめて支払済みにしました。");
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->payableAgencySummaries();
        $transferDate = $request->filled('date')
            ? Carbon::parse($request->query('date'))
            : $this->nextTransferDate();

        $recipients = $rows->map(fn (array $row) => [
            'bank_code' => $row['agency']->bank_code,
            'branch_code' => $row['agency']->bank_branch_code,
            'account_type' => $row['agency']->bank_account_type === BankAccountType::Checking ? '2' : '1',
            'account_no' => $row['agency']->bank_account_number,
            'name' => $row['agency']->bank_account_holder,
            'amount' => $row['total'],
        ])->all();

        $csv = (new ZenginTransferCsvBuilder())->build($recipients, $transferDate);
        $sjis = mb_convert_encoding($csv, 'SJIS-win', 'UTF-8');

        $filename = 'tsunagu_'.$transferDate->format('md').'.csv';

        return response($sjis, 200, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * 現在支払い可能（累計未払い額が繰り越し閾値以上）なパートナーごとに、
     * 紹介報酬・パートナー10%・共創パートナー30%を合算した内訳を返す。
     * 一括支払い・CSV出力の両方で、月の絞り込みに関係なく「今の未払い全額」を対象にするために使う。
     */
    private function payableAgencySummaries(): Collection
    {
        $agencyIdsWithUnpaid = collect()
            ->merge(
                Contract::where('payment_status', PaymentStatus::Unpaid)->with('inquiry')->get()->pluck('inquiry.agency_id')
            )
            ->merge(
                ReferralCommission::where('payment_status', PaymentStatus::Unpaid)->pluck('referrer_agency_id')
            )
            ->merge(
                CollaborationReward::where('status', CollaborationRewardStatus::Approved)
                    ->where('payment_status', PaymentStatus::Unpaid)
                    ->get()
                    ->map(fn (CollaborationReward $reward) => Project::where('client_name', $reward->client_name)
                        ->whereNotNull('referrer_agency_id')
                        ->value('referrer_agency_id'))
            )
            ->unique()->filter()->values();

        return Agency::whereIn('id', $agencyIdsWithUnpaid)->get()
            ->map(fn (Agency $agency) => ['agency' => $agency, ...$agency->pendingPayoutBreakdown()])
            ->filter(fn (array $row) => $row['total'] >= self::CARRY_OVER_THRESHOLD)
            ->sortByDesc('total')
            ->values();
    }

    private function markAgencyPaid(Agency $agency, LineMessagingService $lineMessaging): int
    {
        $unpaidContracts = $agency->contracts()->where('payment_status', PaymentStatus::Unpaid)->get();
        $unpaidCommissions = $agency->referralCommissions()->where('payment_status', PaymentStatus::Unpaid)->get();

        $clientNames = $agency->projects()->whereNotNull('client_name')->distinct()->pluck('client_name');

        $unpaidRewards = CollaborationReward::whereIn('client_name', $clientNames)
            ->where('status', CollaborationRewardStatus::Approved)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->get();

        $total = $unpaidContracts->sum('agency_reward_amount')
            + $unpaidCommissions->sum('amount')
            + $unpaidRewards->sum('reward_amount');

        if ($total <= 0) {
            return 0;
        }

        $now = now();

        foreach ($unpaidContracts as $contract) {
            $contract->update(['payment_status' => PaymentStatus::Paid, 'paid_at' => $now]);
        }

        foreach ($unpaidCommissions as $commission) {
            $commission->update(['payment_status' => PaymentStatus::Paid, 'paid_at' => $now]);
        }

        foreach ($unpaidRewards as $reward) {
            $reward->update(['payment_status' => PaymentStatus::Paid, 'paid_at' => $now]);
        }

        $this->notifyPaymentCompleted($agency, (int) $total, $lineMessaging);

        return (int) $total;
    }

    private function nextTransferDate(): Carbon
    {
        $today = now();

        return $today->day <= 5
            ? $today->copy()->startOfMonth()->addDays(4)
            : $today->copy()->addMonthNoOverflow()->startOfMonth()->addDays(4);
    }

    public function revertAll(Agency $agency): RedirectResponse
    {
        $paidContracts = $agency->contracts()->where('payment_status', PaymentStatus::Paid)->get();
        $paidCommissions = $agency->referralCommissions()->where('payment_status', PaymentStatus::Paid)->get();

        $clientNames = $agency->projects()->whereNotNull('client_name')->distinct()->pluck('client_name');

        $paidRewards = CollaborationReward::whereIn('client_name', $clientNames)
            ->where('status', CollaborationRewardStatus::Approved)
            ->where('payment_status', PaymentStatus::Paid)
            ->get();

        if ($paidContracts->isEmpty() && $paidCommissions->isEmpty() && $paidRewards->isEmpty()) {
            return redirect()->route('admin.payments.show', $agency)->with('status', '支払済みの項目がありませんでした。');
        }

        foreach ($paidContracts as $contract) {
            $contract->update(['payment_status' => PaymentStatus::Unpaid, 'paid_at' => null]);
        }

        foreach ($paidCommissions as $commission) {
            $commission->update(['payment_status' => PaymentStatus::Unpaid, 'paid_at' => null]);
        }

        foreach ($paidRewards as $reward) {
            $reward->update(['payment_status' => PaymentStatus::Unpaid, 'paid_at' => null]);
        }

        return redirect()->route('admin.payments.show', $agency)->with('status', 'まとめて未払いに戻しました。');
    }

    public function update(Contract $contract, LineMessagingService $lineMessaging): RedirectResponse
    {
        $contract->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $agency = $contract->inquiry->agency;

        $this->notifyPaymentCompleted($agency, (int) $contract->agency_reward_amount, $lineMessaging);

        return redirect()->route('admin.payments.show', $agency)->with('status', '支払済みにしました。');
    }

    public function revert(Contract $contract): RedirectResponse
    {
        $contract->update([
            'payment_status' => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);

        return redirect()->route('admin.payments.show', $contract->inquiry->agency)->with('status', '未払いに戻しました。');
    }

    public function updateReferralCommission(ReferralCommission $referralCommission, LineMessagingService $lineMessaging): RedirectResponse
    {
        $referralCommission->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->notifyPaymentCompleted($referralCommission->referrerAgency, (int) $referralCommission->amount, $lineMessaging);

        return redirect()->route('admin.payments.show', $referralCommission->referrerAgency)->with('status', 'パートナー10%を支払済みにしました。');
    }

    public function revertReferralCommission(ReferralCommission $referralCommission): RedirectResponse
    {
        $referralCommission->update([
            'payment_status' => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);

        return redirect()->route('admin.payments.show', $referralCommission->referrerAgency)->with('status', 'パートナー10%を未払いに戻しました。');
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

        return redirect()->route('admin.payments.show', $referrerAgency)->with('status', '共創パートナー30%を支払済みにしました。');
    }

    public function revertCollaborationReward(CollaborationReward $collaborationReward): RedirectResponse
    {
        $collaborationReward->update([
            'payment_status' => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);

        $referrerAgency = Project::where('client_name', $collaborationReward->client_name)
            ->whereNotNull('referrer_agency_id')
            ->with('referrerAgency')
            ->first()?->referrerAgency;

        return redirect()->route('admin.payments.show', $referrerAgency)->with('status', '共創パートナー30%を未払いに戻しました。');
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

        $lineMessaging->sendPush(LineChannel::Partner, $agency->line_uid, $message);
    }
}
