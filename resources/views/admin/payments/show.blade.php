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
                <th class="px-4 py-3 font-medium w-28"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($contracts as $contract)
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td class="px-4 py-3">{{ $contract->effectiveProject()->name }}</td>
                    <td class="px-4 py-3">{{ $contract->inquiry->name }}</td>
                    <td class="px-4 py-3">¥{{ number_format($contract->agency_reward_amount) }}</td>
                    <td class="px-4 py-3">{{ $contract->payment_due_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ match ($contract->payment_status) { \App\Enums\PaymentStatus::Paid => 'text-green-700', \App\Enums\PaymentStatus::InternalProcessing => 'text-gray-500', default => 'text-amber-700' } }}">
                            {{ $contract->payment_status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ optional($contract->paid_at)->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        @if ($contract->payment_status === \App\Enums\PaymentStatus::Unpaid)
                            <button type="button" onclick="tsnOpenPayModal('{{ route('admin.payments.update', $contract) }}')" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">支払済み</button>
                        @elseif ($contract->payment_status === \App\Enums\PaymentStatus::Paid)
                            <button type="button" onclick="tsnOpenRevertModal('{{ route('admin.payments.revert', $contract) }}')" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded">取消し</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">紹介報酬データがありません。</td>
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
                <th class="px-4 py-3 font-medium w-28"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($commissions as $commission)
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td class="px-4 py-3">{{ $commission->sourceAgency->name }}</td>
                    <td class="px-4 py-3">¥{{ number_format($commission->amount) }}</td>
                    <td class="px-4 py-3">{{ $commission->payment_due_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ match ($commission->payment_status) { \App\Enums\PaymentStatus::Paid => 'text-green-700', \App\Enums\PaymentStatus::InternalProcessing => 'text-gray-500', default => 'text-amber-700' } }}">
                            {{ $commission->payment_status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ optional($commission->paid_at)->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        @if ($commission->payment_status === \App\Enums\PaymentStatus::Unpaid)
                            <button type="button" onclick="tsnOpenPayModal('{{ route('admin.payments.referral-commissions.update', $commission) }}')" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">支払済み</button>
                        @elseif ($commission->payment_status === \App\Enums\PaymentStatus::Paid)
                            <button type="button" onclick="tsnOpenRevertModal('{{ route('admin.payments.referral-commissions.revert', $commission) }}')" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded">取消し</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">パートナー10%のデータがありません。</td>
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
                <th class="px-4 py-3 font-medium w-28"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($collaborationRewards as $reward)
                <tr class="even:bg-gray-50 hover:bg-gray-100">
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
                    <td class="px-4 py-3">
                        @if ($reward->payment_status === \App\Enums\PaymentStatus::Unpaid)
                            <button type="button" onclick="tsnOpenPayModal('{{ route('admin.payments.collaboration-rewards.update', $reward) }}')" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">支払済み</button>
                        @elseif ($reward->payment_status === \App\Enums\PaymentStatus::Paid)
                            <button type="button" onclick="tsnOpenRevertModal('{{ route('admin.payments.collaboration-rewards.revert', $reward) }}')" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded">取消し</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">共創パートナー30%のデータがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="pay-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">
    <div class="bg-white rounded-lg p-6 max-w-sm w-full">
        <p class="text-sm text-gray-700 mb-4">支払済みにしますか？</p>
        <form method="POST" id="pay-modal-form">
            @csrf
            @method('PATCH')
            <label class="flex items-center gap-2 text-sm mb-4">
                <input type="checkbox" name="skip_line_notify" value="1">
                LINE通知を送らない
            </label>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="tsnCloseModal('pay-modal')" class="text-sm text-gray-500 px-4 py-2">キャンセル</button>
                <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">支払済みにする</button>
            </div>
        </form>
    </div>
</div>

<div id="revert-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">
    <div class="bg-white rounded-lg p-6 max-w-sm w-full">
        <p class="text-sm text-gray-700 mb-4">未払いに戻しますか？</p>
        <form method="POST" id="revert-modal-form">
            @csrf
            @method('PATCH')
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="tsnCloseModal('revert-modal')" class="text-sm text-gray-500 px-4 py-2">キャンセル</button>
                <button type="submit" class="text-sm bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md px-4 py-2">未払いに戻す</button>
            </div>
        </form>
    </div>
</div>

<script>
function tsnOpenPayModal(actionUrl) {
    document.getElementById('pay-modal-form').action = actionUrl;
    document.getElementById('pay-modal-form').querySelector('input[name="skip_line_notify"]').checked = false;
    document.getElementById('pay-modal').classList.remove('hidden');
}
function tsnOpenRevertModal(actionUrl) {
    document.getElementById('revert-modal-form').action = actionUrl;
    document.getElementById('revert-modal').classList.remove('hidden');
}
function tsnCloseModal(id) {
    document.getElementById(id).classList.add('hidden');
}
</script>
@endsection
