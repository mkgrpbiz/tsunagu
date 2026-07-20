@extends('layouts.admin')

@section('title', '共創パートナー紹介')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">共創パートナー紹介</h1>
    <a href="{{ route('admin.notification-message-settings.collaboration-referrals.edit') }}" class="text-sm text-blue-600 hover:underline">LINE通知設定</a>
</div>

@php
    $statusTabs = [
        'all' => ['label' => 'すべて', 'color' => 'bg-gray-500'],
        'pending' => ['label' => '審査中', 'color' => 'bg-amber-500'],
        'approved' => ['label' => '承認済', 'color' => 'bg-green-500'],
        'rejected' => ['label' => '見送り', 'color' => 'bg-red-500'],
    ];
@endphp
<div class="flex border-b border-gray-200 mb-4">
    @foreach ($statusTabs as $key => $tab)
        @php $count = $key === 'all' ? $totalCount : $statusCounts->get($key, 0); @endphp
        <a href="{{ route('admin.collaboration-referrals.index', $key === 'all' ? [] : ['status' => $key]) }}"
           class="flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
                  {{ $status === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $tab['label'] }}
            <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white {{ $tab['color'] }}">{{ $count }}</span>
        </a>
    @endforeach
</div>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">申請日時</th>
                <th class="px-4 py-3 font-medium">紹介元パートナー</th>
                <th class="px-4 py-3 font-medium">紹介先の事業内容</th>
                <th class="px-4 py-3 font-medium w-24 text-center">詳細</th>
                <th class="px-4 py-3 font-medium w-28">ステータス</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($referrals as $referral)
                @php
                    $statusColor = match ($referral->status) {
                        \App\Enums\CollaborationReferralStatus::Approved => 'bg-green-50 text-green-700 border-green-200',
                        \App\Enums\CollaborationReferralStatus::Rejected => 'bg-red-50 text-red-700 border-red-200',
                        \App\Enums\CollaborationReferralStatus::Pending => 'bg-amber-50 text-amber-700 border-amber-200',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap">{{ $referral->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">{{ $referral->agency->name }}（{{ $referral->agency->referral_code }}）</td>
                    <td class="px-4 py-3 text-gray-600 truncate max-w-xs">{{ $referral->referred_business }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-1 justify-center flex-wrap">
                            <a href="{{ route('admin.collaboration-referrals.show', $referral) }}" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">詳細</a>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $statusColor }}">
                            {{ $referral->status->label() }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">共創パートナーの紹介はまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
