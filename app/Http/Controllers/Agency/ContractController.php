<?php

namespace App\Http\Controllers\Agency;

use App\Enums\CollaborationRewardStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\CollaborationReward;
use App\Models\CompanyProfile;
use App\Models\Contract;
use App\Models\Project;
use App\Models\ReferralCommission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $agency = Auth::guard('agency')->user();

        $data = $this->buildMonthData($agency, $request->query('month'));

        return view('agency.contracts.index', $data);
    }

    public function downloadStatement(Request $request): Response
    {
        $agency = Auth::guard('agency')->user();

        $month = $request->query('month');

        if (! $month || $month === 'all' || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            abort(404);
        }

        $data = $this->buildMonthData($agency, $month, forceMonth: true);

        // 支払通知書はその月の確定実績を示すものなので、未払い/支払済みを問わず全額を合計する。
        $statementTotal = $data['contracts']->sum('agency_reward_amount')
            + $data['referralCommissionGroups']->sum('total')
            + $data['collaborationRewardRows']->sum('rewardAmount');

        $statementNumber = str_replace('-', '', $data['month']).'-'.str_pad((string) $agency->id, 4, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('agency.contracts.statement_pdf', [
            ...$data,
            'monthlyTotal' => $statementTotal,
            'agency' => $agency,
            'companyProfile' => CompanyProfile::current(),
            'issuedAt' => now(),
            'statementNumber' => $statementNumber,
        ])->setPaper('a4');

        return $pdf->download("支払通知書_{$data['month']}.pdf");
    }

    private function buildMonthData(Agency $agency, ?string $month, bool $forceMonth = false): array
    {
        // パートナー単価0円の契約は実際の報酬が発生しないため、パートナー向け画面・PDFには表示しない。
        $contracts = $agency->contracts()
            ->with('inquiry.project')
            ->where('agency_reward_amount', '>', 0)
            ->orderByDesc('deposit_date')
            ->get();

        $referralCommissions = ReferralCommission::where('referrer_agency_id', $agency->id)
            ->with('sourceAgency')
            ->orderByDesc('payment_due_date')
            ->get();

        $clientNames = Project::where('referrer_agency_id', $agency->id)
            ->whereNotNull('client_name')
            ->distinct()
            ->pluck('client_name');

        $collaborationRewards = CollaborationReward::whereIn('client_name', $clientNames)
            ->where('status', CollaborationRewardStatus::Approved)
            ->orderByDesc('month')
            ->get();

        $clientContracts = Contract::whereHas('inquiry.project', fn ($query) => $query->whereIn('client_name', $clientNames))
            ->with('inquiry.project')
            ->get();

        // データが無くても当月は選択肢・初期選択に必ず含める（前月のまま止まって見えないように）
        $months = $contracts->map(fn (Contract $contract) => $contract->deposit_date->format('Y-m'))->toBase()
            ->merge($referralCommissions->map(fn (ReferralCommission $commission) => $commission->payment_due_date->format('Y-m')))
            ->merge($collaborationRewards->map(fn (CollaborationReward $reward) => $reward->month->format('Y-m')))
            ->push(now()->format('Y-m'))
            ->unique()->sortDesc()->values();

        $month = $month ?? now()->format('Y-m');
        $month = ($month === 'all' && ! $forceMonth) ? null : $month;

        $monthContracts = $contracts->when($month, fn ($collection) => $collection->filter(
            fn (Contract $contract) => $contract->deposit_date->format('Y-m') === $month
        ))->values();

        $monthReferralCommissions = $referralCommissions->when($month, fn ($collection) => $collection->filter(
            fn (ReferralCommission $commission) => $commission->payment_due_date->format('Y-m') === $month
        ))->values();

        $monthCollaborationRewards = $collaborationRewards->when($month, fn ($collection) => $collection->filter(
            fn (CollaborationReward $reward) => $reward->month->format('Y-m') === $month
        ))->values();

        // 繰り越し報酬: 選択中の月より前の月の未払い分（1,000円未満で支払われず繰り越されたまま残っている額）。
        // 「今この瞬間の累計未払い額」で判定すると、当月分が積み上がって閾値を超えた途端に
        // 過去の繰り越し分が画面上どの月を見ても¥0になり、繰り越されていないように見えるバグがあった。
        $carryOverAmount = $month
            ? $contracts->where('payment_status', PaymentStatus::Unpaid)
                ->filter(fn (Contract $contract) => $contract->deposit_date->format('Y-m') < $month)
                ->sum('agency_reward_amount')
            + $referralCommissions->where('payment_status', PaymentStatus::Unpaid)
                ->filter(fn (ReferralCommission $commission) => $commission->payment_due_date->format('Y-m') < $month)
                ->sum('amount')
            + $collaborationRewards->where('payment_status', PaymentStatus::Unpaid)
                ->filter(fn (CollaborationReward $reward) => $reward->month->format('Y-m') < $month)
                ->sum('reward_amount')
            : 0;

        $referralCommissionGroups = $monthReferralCommissions
            ->groupBy(fn (ReferralCommission $commission) => $commission->source_agency_id.'|'.$commission->payment_due_date->format('Y-m-d'))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'sourceAgency' => $first->sourceAgency,
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                    'paymentDueDate' => $first->payment_due_date,
                ];
            })
            ->sortByDesc(fn ($row) => $row['paymentDueDate'])
            ->values();

        $collaborationRewardRows = $monthCollaborationRewards->map(function (CollaborationReward $reward) use ($clientContracts) {
            $matching = $clientContracts->filter(
                fn (Contract $contract) => $contract->effectiveProject()->client_name === $reward->client_name
                    && $contract->deposit_date->format('Y-m') === $reward->month->format('Y-m')
            );

            return [
                'clientName' => $reward->client_name,
                'projectCount' => $matching->pluck('inquiry.project.id')->unique()->count(),
                'depositCount' => $matching->count(),
                'rewardAmount' => $reward->reward_amount,
            ];
        })->values();

        // パートナー側は社内処理（internal_processing）分も自分の報酬として合計に含める
        $monthlyPayoutTotal = $monthContracts->sum('agency_reward_amount');
        $monthlyReferralTotal = $monthReferralCommissions->sum('amount');
        $monthlyCollaborationRewardTotal = $monthCollaborationRewards->sum('reward_amount');
        $monthlyTotal = $monthlyPayoutTotal + $monthlyReferralTotal + $monthlyCollaborationRewardTotal;

        $payableItemsCount = $monthContracts->count()
            + $monthReferralCommissions->count()
            + $monthCollaborationRewards->count();

        // パートナーから見ればどちらも自分の報酬が確定済みなので、社内処理も支払済みと同様にカウントする
        $paidItemsCount = $monthContracts->where('payment_status', '!=', PaymentStatus::Unpaid)->count()
            + $monthReferralCommissions->where('payment_status', '!=', PaymentStatus::Unpaid)->count()
            + $monthCollaborationRewards->where('payment_status', '!=', PaymentStatus::Unpaid)->count();

        return [
            'contracts' => $monthContracts,
            'monthlyPayoutTotal' => $monthlyPayoutTotal,
            'referralCommissionGroups' => $referralCommissionGroups,
            'monthlyReferralTotal' => $monthlyReferralTotal,
            'collaborationRewardRows' => $collaborationRewardRows,
            'monthlyCollaborationRewardTotal' => $monthlyCollaborationRewardTotal,
            'monthlyTotal' => $monthlyTotal,
            'paidItemsCount' => $paidItemsCount,
            'payableItemsCount' => $payableItemsCount,
            'months' => $months,
            'month' => $month,
            'carryOverAmount' => $carryOverAmount,
        ];
    }
}
