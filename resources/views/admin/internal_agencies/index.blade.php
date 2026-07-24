@extends('layouts.admin')

@section('title', '社内運用アカウント')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">社内運用アカウント</h1>
    <a href="{{ route('admin.internal-agencies.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">コードを指定して新規作成</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">既存のパートナーから追加</h2>
    <form method="GET" action="{{ route('admin.internal-agencies.index') }}" class="flex gap-2 mb-4">
        <input type="text" name="search" value="{{ $search }}" placeholder="会員番号・名前・フリガナで検索"
               class="w-72 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        <button type="submit" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md px-4 py-2">検索</button>
    </form>

    @if ($search !== '')
        <div class="border border-gray-200 rounded-md divide-y divide-gray-100">
            @forelse ($searchResults as $result)
                <div class="flex items-center justify-between px-4 py-2 text-sm">
                    <span>{{ $result->name }}（{{ $result->name_kana }} / {{ $result->referral_code }}）</span>
                    <form method="POST" action="{{ route('admin.internal-agencies.toggle', $result) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded">社内運用アカウントにする</button>
                    </form>
                </div>
            @empty
                <p class="px-4 py-3 text-sm text-gray-400">該当するパートナーが見つかりません。</p>
            @endforelse
        </div>
    @endif
</div>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">名前</th>
                <th class="px-4 py-3 font-medium">会員番号</th>
                <th class="px-4 py-3 font-medium">紹介URL</th>
                <th class="px-4 py-3 font-medium">紹介人数</th>
                <th class="px-4 py-3 font-medium">紹介報酬累計（社内処理）</th>
                <th class="px-4 py-3 font-medium w-32 text-center">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($internalAgencies as $agency)
                <tr>
                    <td class="px-4 py-3">{{ $agency->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->referral_code }}</td>
                    <td class="px-4 py-3">
                        <input type="text" readonly value="{{ url('/agency/register?ref='.$agency->referral_code) }}"
                               class="w-full max-w-xs rounded-md border border-gray-300 text-xs px-2 py-1 bg-gray-50">
                    </td>
                    <td class="px-4 py-3">{{ $agency->referrals_count }}</td>
                    <td class="px-4 py-3">¥{{ number_format($agency->referral_commission_total) }}</td>
                    <td class="px-4 py-3 text-center">
                        <form method="POST" action="{{ route('admin.internal-agencies.toggle', $agency) }}" onsubmit="return confirm('社内運用アカウントの指定を解除しますか？');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded">解除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">社内運用アカウントはまだありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
