<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ReferralCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $allContracts = Contract::with(['inquiry.project', 'inquiry.agency'])
            ->orderBy('payment_due_date')
            ->get();

        $allCommissions = ReferralCommission::with(['referrerAgency', 'sourceAgency'])
            ->orderBy('payment_due_date')
            ->get();

        $months = $allContracts->map(fn (Contract $contract) => $contract->payment_due_date->format('Y-m'))->toBase()
            ->merge($allCommissions->map(fn (ReferralCommission $commission) => $commission->payment_due_date->format('Y-m')))
            ->unique()->sortDesc()->values();

        $month = $request->query('month', $months->first());
        $month = $month === 'all' ? null : $month;

        $monthContracts = $allContracts->when($month, fn ($collection) => $collection->filter(
            fn (Contract $contract) => $contract->payment_due_date->format('Y-m') === $month
        ));

        $monthCommissions = $allCommissions->when($month, fn ($collection) => $collection->filter(
            fn (ReferralCommission $commission) => $commission->payment_due_date->format('Y-m') === $month
        ));

        $monthlyTotal = $monthContracts->where('payment_status', PaymentStatus::Unpaid)->sum('agency_reward_amount')
            + $monthCommissions->where('payment_status', PaymentStatus::Unpaid)->sum('amount');

        $cumulativeTotal = $allContracts->where('payment_status', PaymentStatus::Unpaid)->sum('agency_reward_amount')
            + $allCommissions->where('payment_status', PaymentStatus::Unpaid)->sum('amount');

        return view('admin.payments.index', [
            'contractsByAgency' => $monthContracts->groupBy(fn (Contract $contract) => $contract->inquiry->agency->name),
            'referralCommissionsByAgency' => $monthCommissions->groupBy(fn (ReferralCommission $commission) => $commission->referrerAgency->name),
            'months' => $months,
            'month' => $month,
            'monthlyTotal' => $monthlyTotal,
            'cumulativeTotal' => $cumulativeTotal,
        ]);
    }

    public function update(Contract $contract): RedirectResponse
    {
        $contract->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

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

    public function updateReferralCommission(ReferralCommission $referralCommission): RedirectResponse
    {
        $referralCommission->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        return redirect()->route('admin.payments.index')->with('status', '紹介報酬を支払済みにしました。');
    }

    public function revertReferralCommission(ReferralCommission $referralCommission): RedirectResponse
    {
        $referralCommission->update([
            'payment_status' => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);

        return redirect()->route('admin.payments.index')->with('status', '紹介報酬を未払いに戻しました。');
    }
}
