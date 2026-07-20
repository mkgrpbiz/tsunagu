@extends('layouts.admin')

@section('title', $client['client_name'].' - 共創報酬内訳')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">{{ $client['client_name'] }} - 共創報酬内訳</h1>
    <a href="{{ route('admin.collaboration-rewards.index') }}" class="text-sm text-blue-600 hover:underline">一覧に戻る</a>
</div>

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
@endsection
