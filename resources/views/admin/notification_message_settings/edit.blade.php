@extends('layouts.admin')

@section('title', $title)

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">{{ $title }}</h1>
    <a href="{{ route($backRoute) }}" class="text-sm text-blue-600 hover:underline">一覧に戻る</a>
</div>

<p class="text-sm text-gray-500 mb-4">審査ステータスを「承認」「見送り」に変更した際、LINE連携済みのパートナーへ送信される文面です。</p>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-2xl">
    <form method="POST" action="{{ route($updateRoute) }}">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label for="approved_message" class="block text-sm font-medium text-gray-700 mb-1">承認時のメッセージ</label>
            <textarea name="approved_message" id="approved_message" rows="4"
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('approved_message', $setting->approved_message) }}</textarea>
            @error('approved_message')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-2">
            <label for="rejected_message" class="block text-sm font-medium text-gray-700 mb-1">見送り時のメッセージ</label>
            <textarea name="rejected_message" id="rejected_message" rows="4"
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('rejected_message', $setting->rejected_message) }}</textarea>
            @error('rejected_message')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        @if ($placeholderHint)
            <p class="text-xs text-gray-500 mb-6">{{ $placeholderHint }}</p>
        @endif

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">
            保存
        </button>
    </form>
</div>
@endsection
