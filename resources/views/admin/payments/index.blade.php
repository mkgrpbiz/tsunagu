@extends('layouts.admin')

@section('title', '支払い管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">支払い管理</h1>
    <a href="{{ route('admin.notification-message-settings.payments.edit') }}" class="text-sm text-blue-600 hover:underline">LINE通知文章を設定</a>
</div>

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

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">{{ $month ? $month.'の支払い予定合計' : '全期間の支払い予定合計' }}</p>
        <p class="text-2xl font-semibold mt-1">¥{{ number_format($monthlyTotal) }}</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">累計未払い合計</p>
        <p class="text-2xl font-semibold mt-1">¥{{ number_format($cumulativeTotal) }}</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">繰り越し予定合計（累計¥1,000未満）</p>
        <p class="text-2xl font-semibold mt-1">¥{{ number_format($carryOverTotal) }}</p>
    </div>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">紹介報酬{{ $month ? '（'.$month.'）' : '' }}</h2>
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
                            @else
                                <form method="POST" action="{{ route('admin.payments.revert', $contract) }}" onsubmit="return confirm('未払いに戻しますか？');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md px-3 py-1.5">未払いに戻す</button>
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

<h2 class="text-sm font-semibold text-gray-700 mt-8 mb-3">パートナー10%{{ $month ? '（'.$month.'）' : '' }}</h2>
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
                            @else
                                <form method="POST" action="{{ route('admin.payments.referral-commissions.revert', $commission) }}" onsubmit="return confirm('未払いに戻しますか？');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md px-3 py-1.5">未払いに戻す</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <p class="text-gray-400 text-center py-10">パートナー10%のデータがありません。</p>
@endforelse

<h2 class="text-sm font-semibold text-gray-700 mt-8 mb-3">共創パートナー30%{{ $month ? '（'.$month.'）' : '' }}</h2>
@forelse ($collaborationRewardsByAgency as $agencyName => $rewards)
    @php
        $agency = $rewards->first()->referrerAgency;
        $unpaidTotal = $rewards->where('payment_status', \App\Enums\PaymentStatus::Unpaid)->sum('reward_amount');
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
                    <th class="px-4 py-3 font-medium">取引先名</th>
                    <th class="px-4 py-3 font-medium">対象月</th>
                    <th class="px-4 py-3 font-medium">報酬額</th>
                    <th class="px-4 py-3 font-medium">支払予定日</th>
                    <th class="px-4 py-3 font-medium">ステータス</th>
                    <th class="px-4 py-3 font-medium">支払日</th>
                    <th class="px-4 py-3 font-medium w-32"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($rewards as $reward)
                    <tr>
                        <td class="px-4 py-3">{{ $reward->client_name }}</td>
                        <td class="px-4 py-3">{{ $reward->month->format('Y-m') }}</td>
                        <td class="px-4 py-3">¥{{ number_format($reward->reward_amount) }}</td>
                        <td class="px-4 py-3">{{ $reward->payment_due_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $reward->payment_status === \App\Enums\PaymentStatus::Paid ? 'text-green-700' : 'text-amber-700' }}">
                                {{ $reward->payment_status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ optional($reward->paid_at)->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            @if ($reward->payment_status === \App\Enums\PaymentStatus::Unpaid)
                                <form method="POST" action="{{ route('admin.payments.collaboration-rewards.update', $reward) }}" onsubmit="return confirm('支払済みにしますか？');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md px-3 py-1.5">支払済みにする</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.payments.collaboration-rewards.revert', $reward) }}" onsubmit="return confirm('未払いに戻しますか？');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md px-3 py-1.5">未払いに戻す</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <p class="text-gray-400 text-center py-10">共創パートナー30%のデータがありません。</p>
@endforelse

<h2 class="text-sm font-semibold text-gray-700 mt-8 mb-3">繰り越し予定</h2>
<p class="text-xs text-gray-500 mb-3">累計の未払い合計（紹介報酬＋パートナー10%＋共創パートナー30%）が¥1,000未満のパートナーです。支払予定には含まれず、翌月以降に繰り越されます。</p>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">パートナー</th>
                <th class="px-4 py-3 font-medium">繰り越し予定額</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($carryOverAgencies as $row)
                <tr>
                    <td class="px-4 py-3">{{ $row['agency']->name }}</td>
                    <td class="px-4 py-3">¥{{ number_format($row['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="px-4 py-6 text-center text-gray-400">繰り越し予定のパートナーはいません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
