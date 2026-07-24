<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Enums\Gender;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\CollaborationReward;
use App\Models\Contract;
use App\Models\Project;
use App\Models\ReferralCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InternalAgencyController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $searchResults = collect();

        if ($search !== '') {
            $searchResults = Agency::where('is_system', false)
                ->where('is_internal_use', false)
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('name_kana', 'like', "%{$search}%")
                        ->orWhere('legacy_code', 'like', "%{$search}%");

                    if (preg_match('/^b0*(\d+)$/i', $search, $matches)) {
                        $q->orWhere('id', (int) $matches[1]);
                    } elseif (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search);
                    }
                })
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();
        }

        $thisMonthStart = now()->copy()->startOfMonth();
        $lastMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();

        $internalAgencies = Agency::withCount('referrals')
            ->where('is_internal_use', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Agency $agency) use ($thisMonthStart, $lastMonthStart) {
                $clientNames = Project::where('referrer_agency_id', $agency->id)
                    ->whereNotNull('client_name')
                    ->pluck('client_name');

                $agency->last_month_total = $this->internalProcessingTotal($agency, $clientNames, $lastMonthStart);
                $agency->this_month_total = $this->internalProcessingTotal($agency, $clientNames, $thisMonthStart);
                $agency->cumulative_total = $this->internalProcessingTotal($agency, $clientNames, null);

                return $agency;
            });

        return view('admin.internal_agencies.index', [
            'internalAgencies' => $internalAgencies,
            'search' => $search,
            'searchResults' => $searchResults,
        ]);
    }

    public function show(Request $request, Agency $agency): View
    {
        $contracts = $agency->contracts()
            ->with(['inquiry.project'])
            ->where('payment_status', PaymentStatus::InternalProcessing)
            ->orderByDesc('deposit_date')
            ->get();

        $commissions = $agency->referralCommissions()
            ->with(['sourceAgency', 'contract'])
            ->where('payment_status', PaymentStatus::InternalProcessing)
            ->get();

        $clientNames = $agency->projects()->whereNotNull('client_name')->distinct()->pluck('client_name');

        $collaborationRewards = CollaborationReward::whereIn('client_name', $clientNames)
            ->where('payment_status', PaymentStatus::InternalProcessing)
            ->get();

        $months = $contracts->map(fn (Contract $contract) => $contract->deposit_date->format('Y-m'))
            ->merge($commissions->map(fn (ReferralCommission $commission) => optional($commission->contract?->deposit_date)->format('Y-m')))
            ->merge($collaborationRewards->map(fn (CollaborationReward $reward) => $reward->month->format('Y-m')))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $month = $request->query('month', $months->first());
        $month = $month === 'all' ? null : $month;

        $filteredContracts = $contracts
            ->when($month, fn ($collection) => $collection->filter(
                fn (Contract $contract) => $contract->deposit_date->format('Y-m') === $month
            ))
            ->sortByDesc('deposit_date')
            ->values();

        $filteredCommissions = $commissions
            ->when($month, fn ($collection) => $collection->filter(
                fn (ReferralCommission $commission) => optional($commission->contract?->deposit_date)->format('Y-m') === $month
            ))
            ->sortByDesc('payment_due_date')
            ->values();

        $filteredCollaborationRewards = $collaborationRewards
            ->when($month, fn ($collection) => $collection->filter(
                fn (CollaborationReward $reward) => $reward->month->format('Y-m') === $month
            ))
            ->sortByDesc('payment_due_date')
            ->values();

        $total = $filteredContracts->sum('agency_reward_amount')
            + $filteredCommissions->sum('amount')
            + $filteredCollaborationRewards->sum('reward_amount');

        return view('admin.internal_agencies.show', [
            'agency' => $agency,
            'contracts' => $filteredContracts,
            'commissions' => $filteredCommissions,
            'collaborationRewards' => $filteredCollaborationRewards,
            'total' => $total,
            'months' => $months,
            'month' => $month,
        ]);
    }

    public function create(): View
    {
        return view('admin.internal_agencies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['required', 'string', 'max:255'],
            'legacy_code' => ['required', 'string', 'max:255', Rule::unique('agencies', 'legacy_code')],
        ]);

        Agency::create([
            'name' => $data['name'],
            'name_kana' => $data['name_kana'],
            'legacy_code' => $data['legacy_code'],
            'gender' => Gender::Other,
            'prefecture' => '社内',
            'phone' => '00000000000',
            'email' => 'internal-'.Str::slug($data['legacy_code']).'@internal.tsunagu.local',
            'password' => 'pass1234',
            'must_change_password' => true,
            'status' => AgencyStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => Auth::id(),
            'is_internal_use' => true,
        ]);

        return redirect()->route('admin.internal-agencies.index')->with('status', '社内運用アカウントを作成しました。');
    }

    public function toggle(Agency $agency): RedirectResponse
    {
        $agency->update(['is_internal_use' => ! $agency->is_internal_use]);

        if ($agency->is_internal_use) {
            $this->convertExistingUnpaidToInternalProcessing($agency);
        }

        return redirect()->route('admin.internal-agencies.index')->with(
            'status',
            $agency->is_internal_use ? '社内運用アカウントに設定しました。' : '社内運用アカウントの指定を解除しました。'
        );
    }

    /**
     * @param  Collection<int, string>  $clientNames
     */
    private function internalProcessingTotal(Agency $agency, Collection $clientNames, ?Carbon $monthStart): int
    {
        $monthEnd = $monthStart?->copy()->endOfMonth();

        $contractTotal = Contract::whereHas('inquiry', fn ($query) => $query->where('agency_id', $agency->id))
            ->where('payment_status', PaymentStatus::InternalProcessing)
            ->when($monthStart, fn ($query) => $query->whereBetween('deposit_date', [$monthStart->toDateString(), $monthEnd->toDateString()]))
            ->sum('agency_reward_amount');

        $commissionTotal = ReferralCommission::where('referrer_agency_id', $agency->id)
            ->where('payment_status', PaymentStatus::InternalProcessing)
            ->when($monthStart, fn ($query) => $query->whereHas(
                'contract',
                fn ($q) => $q->whereBetween('deposit_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ))
            ->sum('amount');

        $rewardTotal = $clientNames->isNotEmpty()
            ? CollaborationReward::whereIn('client_name', $clientNames)
                ->where('payment_status', PaymentStatus::InternalProcessing)
                ->when($monthStart, fn ($query) => $query->whereBetween('month', [$monthStart->toDateString(), $monthEnd->toDateString()]))
                ->sum('reward_amount')
            : 0;

        return $contractTotal + $commissionTotal + $rewardTotal;
    }

    /**
     * フラグを立てた時点で、パートナー10%（Contract）・紹介報酬10%（ReferralCommission）・
     * 共創報酬30%（CollaborationReward）のうち未払いのものは、まとめて社内処理扱いにする。
     */
    private function convertExistingUnpaidToInternalProcessing(Agency $agency): void
    {
        Contract::whereHas('inquiry', fn ($query) => $query->where('agency_id', $agency->id))
            ->where('payment_status', PaymentStatus::Unpaid)
            ->update(['payment_status' => PaymentStatus::InternalProcessing]);

        ReferralCommission::where('referrer_agency_id', $agency->id)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->update(['payment_status' => PaymentStatus::InternalProcessing]);

        $clientNames = Project::where('referrer_agency_id', $agency->id)
            ->whereNotNull('client_name')
            ->pluck('client_name');

        if ($clientNames->isNotEmpty()) {
            CollaborationReward::whereIn('client_name', $clientNames)
                ->where('payment_status', PaymentStatus::Unpaid)
                ->update(['payment_status' => PaymentStatus::InternalProcessing]);
        }
    }
}
