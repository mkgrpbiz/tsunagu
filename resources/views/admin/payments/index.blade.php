@extends('layouts.admin')

@section('title', '支払い管理')

@section('content')
<h1 class="text-xl font-semibold mb-6">支払い管理</h1>

<form method="GET" action="{{ route('admin.payments.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div>
        <label for="month" class="block text-xs font-medium text-gray-700 mb-1">支払予定月で絞り込み</label>
        <div class="flex gap-2">
            <select name="month" id="month" onchange="this.form.submit()" class="rounded-md border border-gray-300 text-sm">
                <option value="" disabled @selected(! $month)>月を選択</option>
                @foreach ($months as $ym)
                    <option value="{{ $ym }}" @selected($month === $ym)>{{ $ym }}</option>
                @endforeach
            </select>
            <button type="submit" name="month" value="all" class="text-sm font-medium rounded-md px-3 {{ ! $month ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">累計</button>
        </div>
    </div>
</form>

<div class="grid md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">{{ $month ? $month.'の支払い予定合計' : '全期間の支払い予定合計' }}</p>
        <p class="text-2xl font-semibold mt-1">¥{{ number_format($monthlyTotal) }}</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">累計未払い合計</p>
        <p class="text-2xl font-semibold mt-1">¥{{ number_format($cumulativeTotal) }}</p>
    </div>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">着金分{{ $month ? '（'.$month.'）' : '' }}</h2>
@forelse ($contractsByAgency as $agencyName => $contracts)
    @php
        $agency = $contracts->first()->inquiry->agency;
        $unpaidTotal = $contracts->where('payment_status', \App\Enums\PaymentStatus::Unpaid)->sum('agency_reward_amount');
    @endphp
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <div>
                <span class="font-semibold">{{ $agencyName }}</span>
                <span class="text-sm text-gray-500 ml-3">
                    支払先: {{ $agency->bank_name }} {{ $agency->bank_branch_name }}
                    ({{ $agency->bank_account_type?->label() }} {{ $agency->bank_account_number }} {{ $agency->bank_account_holder }})
                </span>
            </div>
            <span class="text-sm text-gray-700">未払い合計: ¥{{ number_format($unpaidTotal) }}</span>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">対象案件</th>
                    <th class="px-4 py-3 font-medium">対象着金</th>
                    <th class="px-4 py-3 font-medium">支払予定額</th>
                    <th class="px-4 py-3 font-medium">支払予定日</th>
                    <th class="px-4 py-3 font-medium">ステータス</th>
                    <th class="px-4 py-3 font-medium">支払日</th>
                    <th class="px-4 py-3 font-medium w-32"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($contracts as $contract)
                    <tr>
                        <td class="px-4 py-3">{{ $contract->inquiry->project->name }}</td>
                        <td class="px-4 py-3">{{ $contract->inquiry->name }}</td>
                        <td class="px-4 py-3">¥{{ number_format($contract->agency_reward_amount) }}</td>
                        <td class="px-4 py-3">{{ $contract->payment_due_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $contract->payment_status === \App\Enums\PaymentStatus::Paid ? 'text-green-700' : 'text-amber-700' }}">
                                {{ $contract->payment_status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ optional($contract->paid_at)->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            @if ($contract->payment_status === \App\Enums\PaymentStatus::Unpaid)
                                <form method="POST" action="{{ route('admin.payments.update', $contract) }}" onsubmit="return confirm('支払済みにしますか？');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md px-3 py-1.5">支払済みにする</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <p class="text-gray-400 text-center py-10">着金データがありません。</p>
@endforelse

<h2 class="text-sm font-semibold text-gray-700 mt-8 mb-3">紹介報酬{{ $month ? '（'.$month.'）' : '' }}</h2>
@forelse ($referralCommissionsByAgency as $agencyName => $commissions)
    @php
        $agency = $commissions->first()->referrerAgency;
        $unpaidTotal = $commissions->where('payment_status', \App\Enums\PaymentStatus::Unpaid)->sum('amount');
    @endphp
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <div>
                <span class="font-semibold">{{ $agencyName }}</span>
                <span class="text-sm text-gray-500 ml-3">
                    支払先: {{ $agency->bank_name }} {{ $agency->bank_branch_name }}
                    ({{ $agency->bank_account_type?->label() }} {{ $agency->bank_account_number }} {{ $agency->bank_account_holder }})
                </span>
            </div>
            <span class="text-sm text-gray-700">未払い合計: ¥{{ number_format($unpaidTotal) }}</span>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">紹介先パートナー</th>
                    <th class="px-4 py-3 font-medium">紹介報酬額</th>
                    <th class="px-4 py-3 font-medium">支払予定日</th>
                    <th class="px-4 py-3 font-medium">ステータス</th>
                    <th class="px-4 py-3 font-medium">支払日</th>
                    <th class="px-4 py-3 font-medium w-32"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($commissions as $commission)
                    <tr>
                        <td class="px-4 py-3">{{ $commission->sourceAgency->name }}</td>
                        <td class="px-4 py-3">¥{{ number_format($commission->amount) }}</td>
                        <td class="px-4 py-3">{{ $commission->payment_due_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $commission->payment_status === \App\Enums\PaymentStatus::Paid ? 'text-green-700' : 'text-amber-700' }}">
                                {{ $commission->payment_status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ optional($commission->paid_at)->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            @if ($commission->payment_status === \App\Enums\PaymentStatus::Unpaid)
                                <form method="POST" action="{{ route('admin.payments.referral-commissions.update', $commission) }}" onsubmit="return confirm('支払済みにしますか？');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md px-3 py-1.5">支払済みにする</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <p class="text-gray-400 text-center py-10">紹介報酬データがありません。</p>
@endforelse
@endsection
