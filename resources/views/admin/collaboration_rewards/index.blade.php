@extends('layouts.admin')

@section('title', '共創報酬管理')

@section('content')
<h1 class="text-xl font-semibold mb-6">共創報酬管理</h1>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">会社名</th>
                <th class="px-4 py-3 font-medium">紹介者</th>
                <th class="px-4 py-3 font-medium text-right">売上</th>
                <th class="px-4 py-3 font-medium text-right">パートナー報酬</th>
                <th class="px-4 py-3 font-medium text-right">利益</th>
                <th class="px-4 py-3 font-medium text-right">紹介30%</th>
                <th class="px-4 py-3 font-medium w-24">ステータス</th>
                <th class="px-4 py-3 font-medium w-20 text-center">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($clients as $client)
                @php
                    $isApproved = $client['status_summary'] === \App\Enums\CollaborationRewardStatus::Approved;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ $client['client_name'] }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $client['referrer']->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">¥{{ number_format($client['totals']['revenue']) }}</td>
                    <td class="px-4 py-3 text-right">¥{{ number_format($client['totals']['agency_reward_total']) }}</td>
                    <td class="px-4 py-3 text-right">¥{{ number_format($client['totals']['profit']) }}</td>
                    <td class="px-4 py-3 text-right">{{ $client['referrer'] ? '¥'.number_format($client['totals']['reward_amount']) : '—' }}</td>
                    <td class="px-4 py-3">
                        @if ($client['status_summary'])
                            <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $isApproved ? 'bg-green-50 text-green-700 border-green-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
                                {{ $client['status_summary']->label() }}
                            </span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-1 justify-center flex-wrap">
                            <a href="{{ route('admin.collaboration-rewards.show', $client['client_name']) }}" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">詳細</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-6 text-center text-gray-400">取引先が登録された案件がありません。案件編集画面で取引先名を設定してください。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
