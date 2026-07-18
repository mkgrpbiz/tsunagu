@extends('layouts.agency')

@section('title', '着金・支払い')

@section('content')
<h1 class="text-xl font-semibold mb-6">着金・支払い</h1>

<form method="GET" action="{{ route('agency.contracts.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div>
        <label for="month" class="block text-xs font-medium text-gray-700 mb-1">月で絞り込み</label>
        <div class="flex gap-2">
            <select name="month" id="month" onchange="this.form.submit()" class="rounded-md border border-gray-300 text-sm">
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
        <p class="text-sm text-gray-500">紹介報酬（未払い合計）</p>
        <p class="text-lg font-semibold mt-1">{{ $month ? $month.'：' : '全期間：' }}¥{{ number_format($monthlyPayoutTotal) }}</p>
        @if ($month)
            <p class="text-sm text-gray-500 mt-1">累計：¥{{ number_format($pendingPayoutTotal) }}</p>
        @endif
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">パートナー10%（未払い合計）</p>
        <p class="text-lg font-semibold mt-1">{{ $month ? $month.'：' : '全期間：' }}¥{{ number_format($monthlyReferralTotal) }}</p>
        @if ($month)
            <p class="text-sm text-gray-500 mt-1">累計：¥{{ number_format($pendingReferralTotal) }}</p>
        @endif
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <p class="text-sm text-gray-500">共創パートナー30%（未払い合計）</p>
        <p class="text-lg font-semibold mt-1">{{ $month ? $month.'：' : '全期間：' }}¥{{ number_format($monthlyCollaborationRewardTotal) }}</p>
        @if ($month)
            <p class="text-sm text-gray-500 mt-1">累計：¥{{ number_format($pendingCollaborationRewardTotal) }}</p>
        @endif
    </div>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">紹介報酬{{ $month ? '（'.$month.'）' : '' }}</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-x-auto mb-8">
    <table class="w-full text-sm min-w-max">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">案件名</th>
                <th class="px-4 py-3 font-medium">着金日</th>
                <th class="px-4 py-3 font-medium">パートナー報酬</th>
                <th class="px-4 py-3 font-medium">支払予定日</th>
                <th class="px-4 py-3 font-medium">ステータス</th>
                <th class="px-4 py-3 font-medium">支払日</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($contracts as $contract)
                <tr>
                    <td class="px-4 py-3">{{ $contract->inquiry->project->name }}</td>
                    <td class="px-4 py-3">{{ $contract->deposit_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">¥{{ number_format($contract->agency_reward_amount) }}</td>
                    <td class="px-4 py-3">{{ $contract->payment_due_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ $contract->payment_status === \App\Enums\PaymentStatus::Paid ? 'text-green-700' : 'text-amber-700' }}">
                            {{ $contract->payment_status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ optional($contract->paid_at)->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">着金はまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">パートナー10%{{ $month ? '（'.$month.'）' : '' }}</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
    <table class="w-full text-sm min-w-max">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">紹介先パートナー</th>
                <th class="px-4 py-3 font-medium">紹介報酬額</th>
                <th class="px-4 py-3 font-medium">支払予定日</th>
                <th class="px-4 py-3 font-medium">ステータス</th>
                <th class="px-4 py-3 font-medium">支払日</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($referralCommissions as $commission)
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
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">紹介報酬はまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3 mt-8">共創パートナー30%{{ $month ? '（'.$month.'）' : '' }}</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
    <table class="w-full text-sm min-w-max">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">取引先名</th>
                <th class="px-4 py-3 font-medium">月</th>
                <th class="px-4 py-3 font-medium">報酬額</th>
                <th class="px-4 py-3 font-medium">ステータス</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($collaborationRewards as $reward)
                <tr>
                    <td class="px-4 py-3">{{ $reward->client_name }}</td>
                    <td class="px-4 py-3">{{ $reward->month->format('Y-m') }}</td>
                    <td class="px-4 py-3">¥{{ number_format($reward->reward_amount) }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ $reward->status === \App\Enums\CollaborationRewardStatus::Approved ? 'text-green-700' : 'text-amber-700' }}">
                            {{ $reward->status->label() }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">共創報酬はまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
