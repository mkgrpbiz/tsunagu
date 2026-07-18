@extends('layouts.admin')

@section('title', 'ホームテキスト設定')

@section('content')
<h1 class="text-xl font-semibold mb-6">ホームテキスト設定</h1>
<p class="text-sm text-gray-500 mb-4"><a href="{{ route('admin.home-blocks.index') }}" class="text-blue-600 hover:underline">← ホーム管理に戻る</a></p>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.home-content.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('partials.brand_logo_field', ['homeContent' => $content])

        <div class="mb-4">
            <label for="hero_tagline" class="block text-sm font-medium text-gray-700 mb-1">ヘッダーのタグライン</label>
            <input type="text" name="hero_tagline" id="hero_tagline" value="{{ old('hero_tagline', $content->hero_tagline) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('hero_tagline')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="closing_message" class="block text-sm font-medium text-gray-700 mb-1">締めのメッセージ</label>
            <textarea name="closing_message" id="closing_message" rows="3" required
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('closing_message', $content->closing_message) }}</textarea>
            @error('closing_message')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    </form>
</div>
@endsection
