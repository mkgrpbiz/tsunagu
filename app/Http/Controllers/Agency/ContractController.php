<?php

namespace App\Http\Controllers\Agency;

use App\Enums\CollaborationRewardStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\CollaborationReward;
use App\Models\Contract;
use App\Models\Project;
use App\Models\ReferralCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $agency = Auth::guard('agency')->user();

        $contracts = $agency->contracts()
            ->with('inquiry.project')
            ->orderByDesc('deposit_date')
            ->get();

        $pendingPayoutTotal = $contracts
            ->where('payment_status', PaymentStatus::Unpaid)
            ->sum('agency_reward_amount');

        $referralCommissions = ReferralCommission::where('referrer_agency_id', $agency->id)
            ->with('sourceAgency')
            ->orderByDesc('payment_due_date')
            ->get();

        $pendingReferralTotal = $referralCommissions
            ->where('payment_status', PaymentStatus::Unpaid)
            ->sum('amount');

        $clientNames = Project::where('referrer_agency_id', $agency->id)
            ->whereNotNull('client_name')
            ->distinct()
            ->pluck('client_name');

        $collaborationRewards = CollaborationReward::whereIn('client_name', $clientNames)
            ->where('status', CollaborationRewardStatus::Approved)
            ->orderByDesc('month')
            ->get();

        $pendingCollaborationRewardTotal = $collaborationRewards->sum('reward_amount');

        $clientContracts = Contract::whereHas('inquiry.project', fn ($query) => $query->whereIn('client_name', $clientNames))
            ->with('inquiry.project')
            ->get();

        $months = $contracts->map(fn (Contract $contract) => $contract->deposit_date->format('Y-m'))->toBase()
            ->merge($referralCommissions->map(fn (ReferralCommission $commission) => $commission->payment_due_date->format('Y-m')))
            ->merge($collaborationRewards->map(fn (CollaborationReward $reward) => $reward->month->format('Y-m')))
            ->unique()->sortDesc()->values();

        $month = $request->query('month', $months->first());
        $month = $month === 'all' ? null : $month;

        $monthContracts = $contracts->when($month, fn ($collection) => $collection->filter(
            fn (Contract $contract) => $contract->deposit_date->format('Y-m') === $month
        ))->values();

        $monthReferralCommissions = $referralCommissions->when($month, fn ($collection) => $collection->filter(
            fn (ReferralCommission $commission) => $commission->payment_due_date->format('Y-m') === $month
        ))->values();

        $monthCollaborationRewards = $collaborationRewards->when($month, fn ($collection) => $collection->filter(
            fn (CollaborationReward $reward) => $reward->month->format('Y-m') === $month
        ))->values();

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
                fn (Contract $contract) => $contract->inquiry->project->client_name === $reward->client_name
                    && $contract->deposit_date->format('Y-m') === $reward->month->format('Y-m')
            );

            return [
                'clientName' => $reward->client_name,
                'projectCount' => $matching->pluck('inquiry.project.id')->unique()->count(),
                'depositCount' => $matching->count(),
                'rewardAmount' => $reward->reward_amount,
            ];
        })->values();

        $monthlyPayoutTotal = $monthContracts->where('payment_status', PaymentStatus::Unpaid)->sum('agency_reward_amount');
        $monthlyReferralTotal = $monthReferralCommissions->where('payment_status', PaymentStatus::Unpaid)->sum('amount');
        $monthlyCollaborationRewardTotal = $monthCollaborationRewards->sum('reward_amount');

        return view('agency.contracts.index', [
            'contracts' => $monthContracts,
            'pendingPayoutTotal' => $pendingPayoutTotal,
            'monthlyPayoutTotal' => $monthlyPayoutTotal,
            'referralCommissionGroups' => $referralCommissionGroups,
            'pendingReferralTotal' => $pendingReferralTotal,
            'monthlyReferralTotal' => $monthlyReferralTotal,
            'collaborationRewardRows' => $collaborationRewardRows,
            'pendingCollaborationRewardTotal' => $pendingCollaborationRewardTotal,
            'monthlyCollaborationRewardTotal' => $monthlyCollaborationRewardTotal,
            'months' => $months,
            'month' => $month,
        ]);
    }
}
