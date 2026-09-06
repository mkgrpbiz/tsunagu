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
use App\Models\Project;
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
        $inquiries = Inquiry::with('project')->get();
        $contracts = Contract::with(['project', 'inquiry.project'])->get();
        $referralCommissions = ReferralCommission::with('contract')->get();

        // データが無くても当月は選択肢・初期選択に必ず含める（前月のまま止まって見えないように）
        $months = $agencies->map(fn (Agency $agency) => $agency->created_at->format('Y-m'))->toBase()
            ->merge($collaborationPartners->map(fn (Agency $agency) => ($agency->collaboration_partner_at ?? $agency->created_at)->format('Y-m')))
            ->merge($inquiries->map(fn (Inquiry $inquiry) => $inquiry->inquired_at->format('Y-m')))
            ->merge($contracts->map(fn (Contract $contract) => $contract->deposit_date->format('Y-m')))
            ->push(now()->format('Y-m'))
            ->unique()->sortDesc()->values();

        $month = $request->query('month', now()->format('Y-m'));
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

        $monthInquiries = $month ? $inquiries->filter(fn (Inquiry $i) => $i->inquired_at->format('Y-m') === $month) : $inquiries;

        $projectInquiryCounts = $monthInquiries
            ->filter(fn (Inquiry $i) => $i->project !== null)
            ->groupBy('project_id')
            ->map(fn ($group) => ['project' => $group->first()->project, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        $monthContracts = $month ? $contracts->filter(fn (Contract $c) => $c->deposit_date->format('Y-m') === $month) : $contracts;

        $projectDepositCounts = $monthContracts
            ->filter(fn (Contract $c) => $c->effectiveProject() !== null)
            ->groupBy(fn (Contract $c) => $c->effectiveProject()->id)
            ->map(fn ($group) => ['project' => $group->first()->effectiveProject(), 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        return view('admin.dashboard.index', [
            'months' => $months,
            'month' => $month,
            'summary' => $summary,
            'chartData' => $chartData,
            'carryOverTotal' => $carryOverTotal,
            'alerts' => $this->alerts(),
            'projectInquiryCounts' => $projectInquiryCounts,
            'projectDepositCounts' => $projectDepositCounts,
        ]);
    }

    private function alerts(): array
    {
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
            $this->paymentWorkflowAlert(),
        ];
    }

    /**
     * 支払いフローは「月末締め→1〜5日で振込予約→翌月5日に振込実行→振込確認して支払済みに更新」なので、
     * 今日が何日かによってアラートの内容を出し分ける。以前は支払日（payment_due_date）から一律5日
     * 経過したものだけをカウントしていたが、実際の運用日である「5日」を全く見ておらず紛らわしかった。
     */
    private function paymentWorkflowAlert(): array
    {
        if (now()->day <= 5) {
            return [
                'label' => '1〜5日は先月の締め作業・振込予約を完了させてください',
                'count' => $this->payablePendingCount(PaymentStatus::Unpaid),
                'route' => route('admin.payments.index'),
            ];
        }

        return [
            'label' => '5日以降は振込確認・支払済みに更新してください',
            'count' => $this->payablePendingCount(PaymentStatus::Reserved),
            'route' => route('admin.payments.index'),
        ];
    }

    /**
     * 累計未払い＋振込予約済みが¥1,000未満（繰り越し対象）のパートナーは、そもそも支払い対象に
     * なっていないため、指定ステータスの件数からは除外する。
     */
    private function payablePendingCount(PaymentStatus $status): int
    {
        // 支払期日が未到来（翌月分）の分はまだ今回の対象ではないため含めない
        $contracts = Contract::where('payment_status', $status)->where('payment_due_date', '<=', now())->with('inquiry')->get();
        $commissions = ReferralCommission::where('payment_status', $status)->where('payment_due_date', '<=', now())->get();
        $rewards = CollaborationReward::where('payment_status', $status)->where('payment_due_date', '<=', now())->get();

        $rewardAgencyIds = $rewards->mapWithKeys(fn (CollaborationReward $reward) => [
            $reward->id => Project::where('client_name', $reward->client_name)
                ->whereNotNull('referrer_agency_id')
                ->value('referrer_agency_id'),
        ]);

        $agencyIds = collect()
            ->merge($contracts->map(fn (Contract $c) => $c->inquiry->agency_id))
            ->merge($commissions->pluck('referrer_agency_id'))
            ->merge($rewardAgencyIds->values())
            ->unique()->filter();

        $payableAgencyIds = Agency::whereIn('id', $agencyIds)->get()
            ->filter(fn (Agency $a) => $a->totalPendingPayout() >= 1000)
            ->pluck('id');

        return $contracts->filter(fn (Contract $c) => $payableAgencyIds->contains($c->inquiry->agency_id))->count()
            + $commissions->filter(fn (ReferralCommission $c) => $payableAgencyIds->contains($c->referrer_agency_id))->count()
            + $rewards->filter(fn (CollaborationReward $r) => $payableAgencyIds->contains($rewardAgencyIds[$r->id] ?? null))->count();
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
