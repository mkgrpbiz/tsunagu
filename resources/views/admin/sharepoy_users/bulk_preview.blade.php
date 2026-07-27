@extends('layouts.admin')

@section('title', 'SharePoy+管理 - 一括登録プレビュー')

@section('content')
<h1 class="text-xl font-semibold mb-6">一括登録プレビュー</h1>

@php
    $validLines = collect($lines)->filter(fn (array $line) => $line['error'] === null);
    $errorLines = collect($lines)->filter(fn (array $line) => $line['error'] !== null);
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <p class="text-sm text-gray-700 mb-1">登録・更新対象: {{ $validLines->count() }}件</p>
    @if ($errorLines->isNotEmpty())
        <p class="text-sm text-red-600">不正な行（スキップされます）: {{ $errorLines->count() }}件</p>
    @endif
</div>

@if ($validLines->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-2 font-medium">ユーザーID</th>
                    <th class="text-left px-4 py-2 font-medium">紹介者ID</th>
                    <th class="text-left px-4 py-2 font-medium">名前</th>
                    <th class="text-left px-4 py-2 font-medium">フリガナ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($validLines as $line)
                    <tr>
                        <td class="px-4 py-2">{{ $line['sharepoy_user_id'] }}</td>
                        <td class="px-4 py-2">{{ $line['referrer_sharepoy_user_id'] }}</td>
                        <td class="px-4 py-2">{{ $line['name'] }}</td>
                        <td class="px-4 py-2">{{ $line['name_kana'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if ($errorLines->isNotEmpty())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-sm font-medium text-red-700 mb-2">スキップされる行</p>
        <ul class="text-xs text-red-600 space-y-1">
            @foreach ($errorLines as $line)
                <li>{{ $line['raw'] }} — {{ $line['error'] }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.sharepoy-users.bulk-store') }}" class="flex gap-3">
    @csrf
    <textarea name="pasted_text" hidden>{{ $pastedText }}</textarea>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2" @disabled($validLines->isEmpty())>
        確定して登録
    </button>
    <a href="{{ route('admin.sharepoy-users.index') }}" class="text-sm text-gray-500 px-4 py-2">戻る</a>
</form>
@endsection
