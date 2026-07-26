@extends('layouts.admin')

@section('title', 'アイコン管理')

@section('content')
<h1 class="text-xl font-semibold mb-6">アイコン管理</h1>
<p class="text-sm text-gray-500 mb-4">ブラウザのタブ・スマホのホーム画面に追加した時に表示されるアイコンです。アップロードした画像はそのまま（トリミング等の加工なし）各サイズにリサイズされます。</p>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-2xl">
    @if ($content->app_icon_source_path)
        <div class="mb-6">
            <p class="text-sm font-medium text-gray-700 mb-2">現在のアイコン</p>
            <div class="flex items-end gap-4">
                <div class="text-center">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($content->app_icon_source_path) }}?v={{ $content->updated_at->timestamp }}" alt="" class="h-24 w-24 object-contain border border-gray-200 rounded-md bg-white">
                    <p class="text-xs text-gray-400 mt-1">元画像</p>
                </div>
                <div class="text-center">
                    <img src="{{ asset('favicon-32x32.png') }}?v={{ $content->updated_at->timestamp }}" alt="" class="h-8 w-8 border border-gray-200 rounded bg-white">
                    <p class="text-xs text-gray-400 mt-1">favicon (32px)</p>
                </div>
                <div class="text-center">
                    <img src="{{ asset('apple-touch-icon.png') }}?v={{ $content->updated_at->timestamp }}" alt="" class="h-16 w-16 border border-gray-200 rounded-xl bg-white">
                    <p class="text-xs text-gray-400 mt-1">ホーム画面 (180px)</p>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.app-icon.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="app_icon" class="block text-sm font-medium text-gray-700 mb-1">アイコン画像（正方形推奨）</label>
            <input type="file" name="app_icon" id="app_icon" accept="image/*" required class="w-full text-sm">
            @error('app_icon')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    </form>
</div>
@endsection
