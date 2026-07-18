@extends('layouts.admin')

@section('title', 'パートナー一覧')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">パートナー一覧</h1>
    <a href="{{ route('admin.agencies.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">新規作成</a>
</div>

@php
    $statusTabs = [
        'all' => ['label' => 'すべて', 'color' => 'bg-gray-500'],
        'pending' => ['label' => '審査中', 'color' => 'bg-yellow-500'],
        'approved' => ['label' => '承認済み', 'color' => 'bg-green-500'],
        'rejected' => ['label' => '否認', 'color' => 'bg-red-500'],
        'suspended' => ['label' => '利用停止', 'color' => 'bg-gray-500'],
    ];
@endphp
<div class="flex border-b border-gray-200 mb-4">
    @foreach ($statusTabs as $key => $tab)
        @php $count = $key === 'all' ? $totalCount : $statusCounts->get($key, 0); @endphp
        <a href="{{ route('admin.agencies.index', $key === 'all' ? [] : ['status' => $key]) }}"
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
                <th class="px-4 py-3 font-medium">会社名</th>
                <th class="px-4 py-3 font-medium">名前</th>
                <th class="px-4 py-3 font-medium">フリガナ</th>
                <th class="px-4 py-3 font-medium">本人コード</th>
                <th class="px-4 py-3 font-medium">紹介コード</th>
                <th class="px-4 py-3 font-medium">審査ステータス</th>
                <th class="px-4 py-3 font-medium">登録申請日時</th>
                <th class="px-4 py-3 font-medium">承認日時</th>
                <th class="px-4 py-3 font-medium">問い合わせ数</th>
                <th class="px-4 py-3 font-medium">パートナー紹介数</th>
                <th class="px-4 py-3 font-medium w-40"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($agencies as $agency)
                <tr>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->company_name ?: '—' }}</td>
                    <td class="px-4 py-3">
                        {{ $agency->name }}
                        @if ($agency->is_collaboration_partner)
                            <span class="text-xs font-medium border rounded-full px-1.5 py-0.5 bg-purple-50 text-purple-700 border-purple-200 ml-1">共創</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->name_kana }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->legacy_code ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->legacy_referral_code ?: '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $agency->status->color() }}">{{ $agency->status->label() }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $agency->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ optional($agency->approved_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $agency->inquiries_count }}</td>
                    <td class="px-4 py-3">{{ $agency->referrals_count }}</td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.agencies.show', $agency) }}" class="text-blue-600 hover:underline">詳細</a>
                        <a href="{{ route('admin.agencies.edit', $agency) }}" class="text-blue-600 hover:underline">編集</a>
                        <form method="POST" action="{{ route('admin.agencies.destroy', $agency) }}" class="inline" onsubmit="return confirm('削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">削除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="px-4 py-6 text-center text-gray-400">パートナーがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
