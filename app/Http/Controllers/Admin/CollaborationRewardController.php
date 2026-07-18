<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollaborationRewardStatus;
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
    public function index(Request $request): View
    {
        $clientNames = Project::whereNotNull('client_name')->distinct()->pluck('client_name')->sort()->values();

        $clients = $clientNames->map(function ($clientName) {
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

            return [
                'client_name' => $clientName,
                'referrer' => $referrer,
                'monthly' => $monthly,
                'totals' => [
                    'revenue' => $monthly->sum('revenue'),
                    'agency_reward_total' => $monthly->sum('agency_reward_total'),
                    'profit' => $monthly->sum('profit'),
                    'reward_amount' => $referrer ? $monthly->sum(fn ($row) => $row['reward']->reward_amount ?? 0) : null,
                ],
            ];
        });

        $clients = $clients->sortByDesc(fn ($client) => $client['referrer'] !== null)->values();

        $months = $clients
            ->flatMap(fn ($client) => $client['monthly']->map(fn ($row) => $row['month']->format('Y-m')))
            ->unique()->sortDesc()->values();

        $month = $request->query('month', $months->first());
        $month = $month === 'all' ? null : $month;

        if ($month) {
            $clients = $clients->map(function ($client) use ($month) {
                $client['monthly'] = $client['monthly']->filter(fn ($row) => $row['month']->format('Y-m') === $month)->values();

                return $client;
            });
        }

        return view('admin.collaboration_rewards.index', [
            'clients' => $clients,
            'months' => $months,
            'month' => $month,
        ]);
    }

    public function update(Request $request, CollaborationReward $collaborationReward): RedirectResponse
    {
        $data = $request->validate([
            'reward_amount' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(CollaborationRewardStatus::class)],
        ]);

        $collaborationReward->update($data);

        return redirect()->route('admin.collaboration-rewards.index')->with('status', '共創報酬を更新しました。');
    }
}
