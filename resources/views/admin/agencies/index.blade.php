@extends('layouts.admin')

@section('title', 'パートナー一覧')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">パートナー一覧</h1>
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.notification-message-settings.agencies.edit') }}" class="text-sm text-blue-600 hover:underline">LINE通知設定（審査結果）</a>
        <a href="{{ route('admin.notification-message-settings.line-connected.edit') }}" class="text-sm text-blue-600 hover:underline">LINE通知設定（連携完了）</a>
        <a href="{{ route('admin.agencies.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">新規作成</a>
    </div>
</div>

<details class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <summary class="text-sm font-medium text-gray-700 cursor-pointer select-none">一括追加（スプレッドシートから貼り付け）</summary>
    <p class="text-xs text-gray-500 mt-3 mb-3">
        「タイムスタンプ　本人コード　紹介コード　LINE名　お名前（フルネーム）　フリガナ　お住まい（都道府県）　ご職業　活動内容　電話番号　メールアドレス」の順にタブ区切りで貼り付けてください（ヘッダー行を含めて貼り付けても自動的に無視されます）。初期パスワードは全員 pass1234 になります。
    </p>
    <form method="POST" action="{{ route('admin.agencies.bulk-preview') }}">
        @csrf
        <textarea name="pasted_text" rows="8" required
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <button type="submit" class="mt-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">プレビュー</button>
    </form>
</details>

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
                <th class="px-4 py-3 font-medium">会員番号</th>
                <th class="px-4 py-3 font-medium">紹介者</th>
                <th class="px-4 py-3 font-medium">登録申請日時</th>
                <th class="px-4 py-3 font-medium">審査ステータス</th>
                <th class="px-4 py-3 font-medium">LINE連携</th>
                <th class="px-4 py-3 font-medium">問い合わせ数</th>
                <th class="px-4 py-3 font-medium">パートナー紹介数</th>
                <th class="px-4 py-3 font-medium w-40 text-center">操作</th>
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
                    <td class="px-4 py-3 text-gray-600">{{ $agency->referral_code }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->referredBy?->referral_code ?: '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $agency->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $agency->status->color() }}">{{ $agency->status->label() }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if ($agency->line_uid)
                            <span class="text-xs font-medium border rounded-full px-2 py-1 bg-green-50 text-green-700 border-green-200">連携済</span>
                        @else
                            <span class="text-xs font-medium border rounded-full px-2 py-1 bg-gray-50 text-gray-500 border-gray-200">未連携</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $agency->inquiries_count }}</td>
                    <td class="px-4 py-3">{{ $agency->referrals_count }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-1 justify-center flex-wrap">
                            <a href="{{ route('admin.agencies.show', $agency) }}" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">詳細</a>
                            <a href="{{ route('admin.agencies.edit', $agency) }}" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">編集</a>
                            <form method="POST" action="{{ route('admin.agencies.destroy', $agency) }}" class="inline" onsubmit="return confirm('削除しますか？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">削除</button>
                            </form>
                        </div>
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
