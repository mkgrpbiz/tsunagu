<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Enums\CollaborationPartnerApplicationStatus;
use App\Enums\InquiryStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\CollaborationPartnerApplication;
use App\Models\CollaborationReward;
use App\Models\Contract;
use App\Models\Inquiry;
use App\Models\ReferralCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $agencies = Agency::all();
        $collaborationPartners = Agency::where('is_collaboration_partner', true)->get();
        $inquiries = Inquiry::all();
        $contracts = Contract::all();
        $referralCommissions = ReferralCommission::with('contract')->get();

        $months = $agencies->map(fn (Agency $agency) => $agency->created_at->format('Y-m'))->toBase()
            ->merge($collaborationPartners->map(fn (Agency $agency) => ($agency->collaboration_partner_at ?? $agency->created_at)->format('Y-m')))
            ->merge($inquiries->map(fn (Inquiry $inquiry) => $inquiry->inquired_at->format('Y-m')))
            ->merge($contracts->map(fn (Contract $contract) => $contract->deposit_date->format('Y-m')))
            ->unique()->sortDesc()->values();

        $month = $request->query('month', $months->first());
        $month = $month === 'all' ? null : $month;
        $previousMonth = $month ? Carbon::parse($month.'-01')->subMonth()->format('Y-m') : null;

        $countResolvers = [
            'referral_partners' => [$agencies, fn (Agency $a) => $a->created_at->format('Y-m')],
            'collaboration_partners' => [$collaborationPartners, fn (Agency $a) => ($a->collaboration_partner_at ?? $a->created_at)->format('Y-m')],
            'inquiries' => [$inquiries, fn (Inquiry $i) => $i->inquired_at->format('Y-m')],
            'deposits' => [$contracts, fn (Contract $c) => $c->deposit_date->format('Y-m')],
        ];

        $summary = [];

        foreach ($countResolvers as $key => [$collection, $resolver]) {
            $summary[$key] = $this->buildMetric(
                $month ? $collection->filter(fn ($item) => $resolver($item) === $month)->count() : $collection->count(),
                $month && $previousMonth ? $collection->filter(fn ($item) => $resolver($item) === $previousMonth)->count() : null,
                $collection->count(),
            );
        }

        [$monthRevenue, $monthPayout] = $this->revenueAndPayout($contracts, $referralCommissions, $month);
        [$prevRevenue, $prevPayout] = $month ? $this->revenueAndPayout($contracts, $referralCommissions, $previousMonth) : [null, null];
        [$totalRevenue, $totalPayout] = $this->revenueAndPayout($contracts, $referralCommissions, null);

        $summary['revenue'] = $this->buildMetric($monthRevenue, $prevRevenue, $totalRevenue);
        $summary['payout'] = $this->buildMetric($monthPayout, $prevPayout, $totalPayout);
        $summary['profit'] = $this->buildMetric(
            $monthRevenue - $monthPayout,
            $month ? $prevRevenue - $prevPayout : null,
            $totalRevenue - $totalPayout,
        );

        $chartMonths = collect(range(11, 0))->map(fn ($i) => Carbon::now()->subMonths($i)->format('Y-m'));

        $chartData = $chartMonths->map(function ($ym) use ($agencies, $inquiries, $contracts, $referralCommissions) {
            [$revenue, $payout] = $this->revenueAndPayout($contracts, $referralCommissions, $ym);

            return [
                'month' => $ym,
                'referral_partners' => $agencies->filter(fn (Agency $a) => $a->created_at->format('Y-m') === $ym)->count(),
                'inquiries' => $inquiries->filter(fn (Inquiry $i) => $i->inquired_at->format('Y-m') === $ym)->count(),
                'revenue' => $revenue,
                'profit' => $revenue - $payout,
            ];
        });

        $carryOverTotal = Agency::carryOverSummary()['total'];

        return view('admin.dashboard.index', [
            'months' => $months,
            'month' => $month,
            'summary' => $summary,
            'chartData' => $chartData,
            'carryOverTotal' => $carryOverTotal,
            'alerts' => $this->alerts(),
        ]);
    }

    private function alerts(): array
    {
        $overdueThreshold = now()->subDays(5)->toDateString();

        $overduePayments = Contract::where('payment_status', PaymentStatus::Unpaid)
            ->where('payment_due_date', '<=', $overdueThreshold)
            ->count()
            + ReferralCommission::where('payment_status', PaymentStatus::Unpaid)
                ->where('payment_due_date', '<=', $overdueThreshold)
                ->count()
            + CollaborationReward::where('payment_status', PaymentStatus::Unpaid)
                ->where('payment_due_date', '<=', $overdueThreshold)
                ->count();

        return [
            [
                'label' => 'パートナー登録審査待ち',
                'count' => Agency::where('status', AgencyStatus::Pending)->count(),
                'route' => route('admin.agencies.index', ['status' => AgencyStatus::Pending->value]),
            ],
            [
                'label' => '共創パートナー申請審査待ち',
                'count' => CollaborationPartnerApplication::where('status', CollaborationPartnerApplicationStatus::Pending)->count(),
                'route' => route('admin.collaboration-partner-applications.index', ['status' => CollaborationPartnerApplicationStatus::Pending->value]),
            ],
            [
                'label' => '問い合わせエラー',
                'count' => Inquiry::where('status', InquiryStatus::GuidanceFailed)->count(),
                'route' => route('admin.inquiries.index'),
            ],
            [
                'label' => '支払日から5日経過した未払い',
                'count' => $overduePayments,
                'route' => route('admin.payments.index'),
            ],
        ];
    }

    private function revenueAndPayout($contracts, $referralCommissions, ?string $ym): array
    {
        $monthContracts = $ym ? $contracts->filter(fn (Contract $c) => $c->deposit_date->format('Y-m') === $ym) : $contracts;
        $monthCommissions = $ym ? $referralCommissions->filter(fn (ReferralCommission $c) => $c->contract->deposit_date->format('Y-m') === $ym) : $referralCommissions;

        $revenue = $monthContracts->sum('deposit_amount');
        $payout = $monthContracts->sum('agency_reward_amount') + $monthCommissions->sum('amount');

        return [$revenue, $payout];
    }

    private function buildMetric(int $current, ?int $previous, int $cumulative): array
    {
        $compare = null;

        if ($previous !== null) {
            $diff = $current - $previous;
            $percent = $previous !== 0 ? (int) round(($diff / $previous) * 100) : null;
            $compare = ['diff' => $diff, 'percent' => $percent];
        }

        return [
            'monthly' => $current,
            'cumulative' => $cumulative,
            'compare' => $compare,
        ];
    }
}
