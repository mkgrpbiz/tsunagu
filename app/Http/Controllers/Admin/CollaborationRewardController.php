<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollaborationRewardStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\CollaborationReward;
use App\Models\Contract;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CollaborationRewardController extends Controller
{
    public function index(): View
    {
        $clientNames = Project::whereNotNull('client_name')->distinct()->pluck('client_name')->sort()->values();

        $clients = $clientNames
            ->map(fn ($clientName) => $this->buildClientSummary($clientName))
            ->sortByDesc(fn ($client) => $client['totals']['revenue'])
            ->values();

        return view('admin.collaboration_rewards.index', [
            'clients' => $clients,
        ]);
    }

    public function show(string $clientName): View
    {
        return view('admin.collaboration_rewards.show', [
            'client' => $this->buildClientSummary($clientName),
        ]);
    }

    public function update(Request $request, CollaborationReward $collaborationReward): RedirectResponse
    {
        $data = $request->validate([
            'reward_amount' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(CollaborationRewardStatus::class)],
        ]);

        $collaborationReward->update($data);

        return redirect()
            ->route('admin.collaboration-rewards.show', $collaborationReward->client_name)
            ->with('status', '共創報酬を更新しました。');
    }

    private function buildClientSummary(string $clientName): array
    {
        $referrer = Project::where('client_name', $clientName)
            ->whereNotNull('referrer_agency_id')
            ->with('referrerAgency')
            ->first()?->referrerAgency;

        $contracts = Contract::whereHas('inquiry.project', fn ($query) => $query->where('client_name', $clientName))
            ->get();

        $monthly = $contracts
            ->groupBy(fn (Contract $contract) => $contract->deposit_date->format('Y-m'))
            ->map(function ($group, $ym) use ($clientName, $referrer) {
                $revenue = $group->sum('deposit_amount');
                $agencyRewardTotal = $group->sum('agency_reward_amount');
                $profit = $revenue - $agencyRewardTotal;
                $month = Carbon::parse($ym.'-01');

                $reward = null;

                if ($referrer) {
                    $reward = CollaborationReward::where('client_name', $clientName)
                        ->whereDate('month', $month->toDateString())
                        ->first();

                    if (! $reward) {
                        $reward = CollaborationReward::create([
                            'client_name' => $clientName,
                            'month' => $month->toDateString(),
                            'reward_amount' => (int) round($profit * 0.3),
                            'status' => CollaborationRewardStatus::PendingApproval,
                            'payment_status' => $referrer->is_internal_use ? PaymentStatus::InternalProcessing : PaymentStatus::Unpaid,
                            'payment_due_date' => $month->copy()->addMonthNoOverflow()->day(5),
                        ]);
                    }
                }

                return [
                    'month' => $month,
                    'revenue' => $revenue,
                    'agency_reward_total' => $agencyRewardTotal,
                    'profit' => $profit,
                    'reward' => $reward,
                ];
            })
            ->sortByDesc(fn ($row) => $row['month']->format('Y-m'))
            ->values();

        $rewards = $monthly->pluck('reward')->filter();

        $statusSummary = match (true) {
            $rewards->isEmpty() => null,
            $rewards->contains(fn (CollaborationReward $r) => $r->status === CollaborationRewardStatus::PendingApproval) => CollaborationRewardStatus::PendingApproval,
            default => CollaborationRewardStatus::Approved,
        };

        return [
            'client_name' => $clientName,
            'referrer' => $referrer,
            'monthly' => $monthly,
            'status_summary' => $statusSummary,
            'totals' => [
                'revenue' => $monthly->sum('revenue'),
                'agency_reward_total' => $monthly->sum('agency_reward_total'),
                'profit' => $monthly->sum('profit'),
                'reward_amount' => $referrer ? $monthly->sum(fn ($row) => $row['reward']->reward_amount ?? 0) : null,
            ],
        ];
    }
}
