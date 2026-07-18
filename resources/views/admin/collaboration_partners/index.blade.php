@extends('layouts.admin')

@section('title', '共創パートナー')

@section('content')
<h1 class="text-xl font-semibold mb-6">共創パートナー</h1>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">会社名</th>
                <th class="px-4 py-3 font-medium">名前</th>
                <th class="px-4 py-3 font-medium">フリガナ</th>
                <th class="px-4 py-3 font-medium">会員番号</th>
                <th class="px-4 py-3 font-medium">紹介者</th>
                <th class="px-4 py-3 font-medium">公開案件数</th>
                <th class="px-4 py-3 font-medium w-24"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($agencies as $agency)
                <tr>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->company_name ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $agency->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->name_kana }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->referral_code }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $agency->referredBy?->referral_code ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $agency->projects_count }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.agencies.show', $agency) }}" class="text-blue-600 hover:underline">詳細を表示</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">共創パートナーに指定されているパートナーがいません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
