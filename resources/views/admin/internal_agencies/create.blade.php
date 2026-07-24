@extends('layouts.admin')

@section('title', '社内運用アカウント - 新規作成')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">社内運用アカウント - 新規作成</h1>
    <a href="{{ route('admin.internal-agencies.index') }}" class="text-sm text-blue-600 hover:underline">一覧に戻る</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.internal-agencies.store') }}">
        @csrf

        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="name_kana" class="block text-sm font-medium text-gray-700 mb-1">フリガナ</label>
            <input type="text" name="name_kana" id="name_kana" value="{{ old('name_kana') }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('name_kana')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-6">
            <label for="legacy_code" class="block text-sm font-medium text-gray-700 mb-1">会員番号（コード）</label>
            <input type="text" name="legacy_code" id="legacy_code" value="{{ old('legacy_code') }}" required placeholder="例: B9001"
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <p class="text-xs text-gray-500 mt-1">既存のパートナーと重複しないコードを指定してください。</p>
            @error('legacy_code')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">
            作成する
        </button>
    </form>
</div>
@endsection
