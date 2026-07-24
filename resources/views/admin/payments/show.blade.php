@extends('layouts.admin')

@section('title', $agency->name.' 様 - 支払い詳細')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">{{ $agency->name }} 様 - 支払い詳細</h1>
    <a href="{{ route('admin.payments.index') }}" class="text-sm text-blue-600 hover:underline">一覧に戻る</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">パートナー情報</h2>
    <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
        <div><dt class="text-gray-500">会社名</dt><dd>{{ $agency->company_name ?: '—' }}</dd></div>
        <div><dt class="text-gray-500">名前</dt><dd>{{ $agency->name }}</dd></div>
        <div><dt class="text-gray-500">会員番号</dt><dd>{{ $agency->referral_code }}</dd></div>
        <div><dt class="text-gray-500">振込先</dt>
            <dd>
                {{ $agency->bank_name }} {{ $agency->bank_branch_name }}
                （{{ $agency->bank_account_type?->label() }} {{ $agency->bank_account_number }} {{ $agency->bank_account_holder }}）
            </dd>
        </div>
    </dl>

    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
        <span class="text-sm text-gray-700">未払い合計: <span class="font-semibold">¥{{ number_format($unpaidTotal) }}</span></span>
        <div class="flex gap-2">
            @if ($paidTotal > 0)
                <form method="POST" action="{{ route('admin.payments.revert-all', $agency) }}" onsubmit="return confirm('支払済みの項目をまとめて未払いに戻しますか？');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md px-4 py-2">まとめて未払いに戻す</button>
                </form>
            @endif
            @if ($unpaidTotal > 0)
                <form method="POST" action="{{ route('admin.payments.pay-all', $agency) }}" onsubmit="return confirm('紹介報酬・パートナー10%・共創パートナー30%の未払い分をまとめて支払済みにしますか？');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">まとめて支払済みにする</button>
                </form>
            @endif
        </div>
    </div>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">紹介報酬</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-8">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">対象案件</th>
                <th class="px-4 py-3 font-medium">対象着金</th>
                <th class="px-4 py-3 font-medium">支払予定額</th>
                <th class="px-4 py-3 font-medium">支払予定日</th>
                <th class="px-4 py-3 font-medium">ステータス</th>
                <th class="px-4 py-3 font-medium">支払日</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($contracts as $contract)
                <tr>
                    <td class="px-4 py-3">{{ $contract->inquiry->project->name }}</td>
                    <td class="px-4 py-3">{{ $contract->inquiry->name }}</td>
                    <td class="px-4 py-3">¥{{ number_format($contract->agency_reward_amount) }}</td>
                    <td class="px-4 py-3">{{ $contract->payment_due_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ match ($contract->payment_status) { \App\Enums\PaymentStatus::Paid => 'text-green-700', \App\Enums\PaymentStatus::InternalProcessing => 'text-gray-500', default => 'text-amber-700' } }}">
                            {{ $contract->payment_status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ optional($contract->paid_at)->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">紹介報酬データがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">パートナー10%</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-8">
    <table class="w-full text-sm">
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
            @forelse ($commissions as $commission)
                <tr>
                    <td class="px-4 py-3">{{ $commission->sourceAgency->name }}</td>
                    <td class="px-4 py-3">¥{{ number_format($commission->amount) }}</td>
                    <td class="px-4 py-3">{{ $commission->payment_due_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ match ($commission->payment_status) { \App\Enums\PaymentStatus::Paid => 'text-green-700', \App\Enums\PaymentStatus::InternalProcessing => 'text-gray-500', default => 'text-amber-700' } }}">
                            {{ $commission->payment_status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ optional($commission->paid_at)->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">パートナー10%のデータがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-sm font-semibold text-gray-700 mb-3">共創パートナー30%</h2>
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">取引先名</th>
                <th class="px-4 py-3 font-medium">対象月</th>
                <th class="px-4 py-3 font-medium">報酬額</th>
                <th class="px-4 py-3 font-medium">支払予定日</th>
                <th class="px-4 py-3 font-medium">ステータス</th>
                <th class="px-4 py-3 font-medium">支払日</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($collaborationRewards as $reward)
                <tr>
                    <td class="px-4 py-3">{{ $reward->client_name }}</td>
                    <td class="px-4 py-3">{{ $reward->month->format('Y-m') }}</td>
                    <td class="px-4 py-3">¥{{ number_format($reward->reward_amount) }}</td>
                    <td class="px-4 py-3">{{ $reward->payment_due_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ match ($reward->payment_status) { \App\Enums\PaymentStatus::Paid => 'text-green-700', \App\Enums\PaymentStatus::InternalProcessing => 'text-gray-500', default => 'text-amber-700' } }}">
                            {{ $reward->payment_status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ optional($reward->paid_at)->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">共創パートナー30%のデータがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
