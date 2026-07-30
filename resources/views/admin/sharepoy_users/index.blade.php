@extends('layouts.admin')

@section('title', 'SharePoy+管理')

@section('content')
<h1 class="text-xl font-semibold mb-6">SharePoy+管理</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <form method="GET" action="{{ route('admin.sharepoy-users.index') }}" class="flex gap-2 items-end">
        <div class="flex-1">
            <label for="q" class="block text-sm font-medium text-gray-700 mb-1">ユーザーID・名前・フリガナで検索</label>
            <input type="text" name="q" id="q" value="{{ $q }}"
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">検索</button>
    </form>
</div>

<details class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <summary class="text-sm font-medium text-gray-700 cursor-pointer select-none">一括登録・更新（スプレッドシートから貼り付け）</summary>
    <p class="text-xs text-gray-500 mt-3 mb-3">
        「ユーザーID - 紹介者ID（任意） - 名前 - フリガナ」の順にタブ区切りで貼り付けてください。1行1件、1件のみの貼り付けも可能です。既存のユーザーIDと一致する行は内容を更新します。
    </p>
    <form method="POST" action="{{ route('admin.sharepoy-users.bulk-preview') }}">
        @csrf
        <textarea name="pasted_text" rows="6" required placeholder="U001&#9;U000&#9;宮田麻美&#9;ミヤタマミ"
                  class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs"></textarea>
        <button type="submit" class="mt-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md px-4 py-2">プレビュー</button>
    </form>
</details>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
            <tr>
                <th class="text-left px-4 py-2 font-medium">ユーザーID</th>
                <th class="text-left px-4 py-2 font-medium">紹介者ID</th>
                <th class="text-left px-4 py-2 font-medium">名前</th>
                <th class="text-left px-4 py-2 font-medium">フリガナ</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($sharepoyUsers as $sharepoyUser)
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td class="px-4 py-2">{{ $sharepoyUser->sharepoy_user_id }}</td>
                    <td class="px-4 py-2">{{ $sharepoyUser->referrer_sharepoy_user_id }}</td>
                    <td class="px-4 py-2">{{ $sharepoyUser->name }}</td>
                    <td class="px-4 py-2">{{ $sharepoyUser->name_kana }}</td>
                    <td class="px-4 py-2 text-right">
                        <a href="{{ route('admin.sharepoy-users.show', $sharepoyUser) }}" class="text-blue-600 hover:underline">詳細</a>
                    </td>
                </tr>
            @empty
                <tr class="even:bg-gray-50 hover:bg-gray-100">
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">該当するユーザーがいません</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
