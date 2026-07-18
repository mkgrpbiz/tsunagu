@extends('layouts.admin')

@section('title', '共創報酬管理')

@section('content')
<h1 class="text-xl font-semibold mb-6">共創報酬管理</h1>

<form method="GET" action="{{ route('admin.collaboration-rewards.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div>
        <label for="month" class="block text-xs font-medium text-gray-700 mb-1">月で絞り込み</label>
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

@forelse ($clients as $client)
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <div>
                <p class="font-semibold">{{ $client['client_name'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">紹介者: {{ $client['referrer']->name ?? '—' }}</p>
            </div>
            <div class="text-right text-xs text-gray-500">
                <p>累計売上 ¥{{ number_format($client['totals']['revenue']) }} ／ 累計パートナー報酬 ¥{{ number_format($client['totals']['agency_reward_total']) }}</p>
                <p class="mt-0.5">累計利益 ¥{{ number_format($client['totals']['profit']) }} ／ 累計30%報酬 {{ $client['referrer'] ? '¥'.number_format($client['totals']['reward_amount']) : '-' }}</p>
            </div>
        </div>

        @foreach ($client['monthly'] as $row)
            @continue(! $row['reward'])
            <form id="reward-form-{{ $row['reward']->id }}" method="POST" action="{{ route('admin.collaboration-rewards.update', $row['reward']) }}">
                @csrf
                @method('PUT')
            </form>
        @endforeach

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">月</th>
                    <th class="px-4 py-2 font-medium text-right">売上</th>
                    <th class="px-4 py-2 font-medium text-right">パートナー報酬</th>
                    <th class="px-4 py-2 font-medium text-right">利益</th>
                    <th class="px-4 py-2 font-medium text-right">30%</th>
                    <th class="px-4 py-2 font-medium">ステータス</th>
                    <th class="px-4 py-2 font-medium w-16"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($client['monthly'] as $row)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row['month']->format('Y-m') }}</td>
                        <td class="px-4 py-3 text-right">¥{{ number_format($row['revenue']) }}</td>
                        <td class="px-4 py-3 text-right">¥{{ number_format($row['agency_reward_total']) }}</td>
                        <td class="px-4 py-3 text-right">¥{{ number_format($row['profit']) }}</td>
                        @if ($row['reward'])
                            @php $isApproved = $row['reward']->status === \App\Enums\CollaborationRewardStatus::Approved; @endphp
                            <td class="px-4 py-3 text-right">
                                <input type="number" name="reward_amount" min="0" form="reward-form-{{ $row['reward']->id }}"
                                       value="{{ $row['reward']->reward_amount }}"
                                       class="w-28 rounded-md border border-gray-300 text-sm text-right">
                            </td>
                            <td class="px-4 py-3">
                                <select name="status" form="reward-form-{{ $row['reward']->id }}" class="rounded-md border border-gray-300 text-sm">
                                    <option value="pending_approval" @selected(! $isApproved)>承認待ち</option>
                                    <option value="approved" @selected($isApproved)>承認</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <button type="submit" form="reward-form-{{ $row['reward']->id }}" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded">保存</button>
                            </td>
                        @else
                            <td class="px-4 py-3 text-right text-gray-400">-</td>
                            <td class="px-4 py-3 text-gray-400">-</td>
                            <td class="px-4 py-3"></td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-400">着金実績がありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@empty
    <p class="text-gray-400 text-center py-10">取引先が登録された案件がありません。案件編集画面で取引先名を設定してください。</p>
@endforelse
@endsection
