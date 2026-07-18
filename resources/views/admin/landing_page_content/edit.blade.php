@extends('layouts.admin')

@section('title', 'LPテキスト設定')

@section('content')
<h1 class="text-xl font-semibold mb-6">LPテキスト設定</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.landing-page-content.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('partials.brand_logo_field', ['homeContent' => $homeContent])

        <div class="mb-4">
            <label for="brand_badge_text" class="block text-sm font-medium text-gray-700 mb-1">ロゴ上のバッジテキスト</label>
            <input type="text" name="brand_badge_text" id="brand_badge_text" value="{{ old('brand_badge_text', $content->brand_badge_text) }}"
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <p class="text-xs text-gray-500 mt-1">空欄にするとバッジは表示されません。</p>
            @error('brand_badge_text')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="tagline" class="block text-sm font-medium text-gray-700 mb-1">タグライン</label>
            <input type="text" name="tagline" id="tagline" value="{{ old('tagline', $content->tagline) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('tagline')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="hero_line1" class="block text-sm font-medium text-gray-700 mb-1">見出し（1行目）</label>
            <input type="text" name="hero_line1" id="hero_line1" value="{{ old('hero_line1', $content->hero_line1) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('hero_line1')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="hero_highlight" class="block text-sm font-medium text-gray-700 mb-1">見出し（2行目・強調部分）</label>
            <input type="text" name="hero_highlight" id="hero_highlight" value="{{ old('hero_highlight', $content->hero_highlight) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('hero_highlight')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="hero_suffix" class="block text-sm font-medium text-gray-700 mb-1">見出し（2行目・続き）</label>
            <input type="text" name="hero_suffix" id="hero_suffix" value="{{ old('hero_suffix', $content->hero_suffix) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('hero_suffix')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="border-t border-gray-100 pt-4 mt-2 mb-4">
            <p class="text-sm font-medium text-gray-700 mb-3">メリット</p>
        </div>

        <div class="mb-4">
            <label for="benefits_title" class="block text-sm font-medium text-gray-700 mb-1">見出し</label>
            <input type="text" name="benefits_title" id="benefits_title" value="{{ old('benefits_title', $content->benefits_title) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('benefits_title')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="benefits_body" class="block text-sm font-medium text-gray-700 mb-1">項目一覧</label>
            <textarea name="benefits_body" id="benefits_body" rows="4"
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('benefits_body', $content->benefits_body) }}</textarea>
            <p class="text-xs text-gray-500 mt-1">
                <strong>1行に1項目</strong>、<code>タイトル|説明</code>の形式で入力してください（例: 紹介導線を収益化|既にお持ちのSNS・コミュニティ・人脈などを活かして収益化できます。）。
            </p>
            @error('benefits_body')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="border-t border-gray-100 pt-4 mt-2 mb-4">
            <p class="text-sm font-medium text-gray-700 mb-3">ご参加の流れ</p>
        </div>

        <div class="mb-4">
            <label for="steps_title" class="block text-sm font-medium text-gray-700 mb-1">見出し</label>
            <input type="text" name="steps_title" id="steps_title" value="{{ old('steps_title', $content->steps_title) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('steps_title')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="step1" class="block text-sm font-medium text-gray-700 mb-1">ステップ1</label>
            <input type="text" name="step1" id="step1" value="{{ old('step1', $content->step1) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('step1')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="step2" class="block text-sm font-medium text-gray-700 mb-1">ステップ2</label>
            <input type="text" name="step2" id="step2" value="{{ old('step2', $content->step2) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('step2')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="step3" class="block text-sm font-medium text-gray-700 mb-1">ステップ3</label>
            <input type="text" name="step3" id="step3" value="{{ old('step3', $content->step3) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('step3')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="border-t border-gray-100 pt-4 mt-2 mb-4">
            <p class="text-sm font-medium text-gray-700 mb-3">登録ボタン</p>
        </div>

        <div class="mb-4">
            <label for="cta_text" class="block text-sm font-medium text-gray-700 mb-1">ボタンのテキスト</label>
            <input type="text" name="cta_text" id="cta_text" value="{{ old('cta_text', $content->cta_text) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('cta_text')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    </form>
</div>
@endsection
