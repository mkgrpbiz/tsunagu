@extends('layouts.admin')

@section('title', '会社概要編集')

@section('content')
<h1 class="text-xl font-semibold mb-6">会社概要編集</h1>
<p class="text-sm text-gray-500 mb-4">パートナーマイページのフッターから見られる会社概要ページの内容です。</p>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.company-profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">会社ロゴ</label>

            @if ($profile->logo_path)
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($profile->logo_path) }}" alt="" class="h-10">
                    <label class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input type="checkbox" name="remove_logo" value="1">
                        画像を削除する
                    </label>
                </div>
            @endif

            <input type="file" name="logo" id="logo" accept="image/*" class="w-full text-sm">
            @error('logo')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">会社名</label>
            <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $profile->company_name) }}" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('company_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label for="representative_title" class="block text-sm font-medium text-gray-700 mb-1">代表者肩書き</label>
                <input type="text" name="representative_title" id="representative_title" value="{{ old('representative_title', $profile->representative_title) }}"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('representative_title')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="representative_name" class="block text-sm font-medium text-gray-700 mb-1">代表者名</label>
                <input type="text" name="representative_name" id="representative_name" value="{{ old('representative_name', $profile->representative_name) }}"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('representative_name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-4">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">所在地</label>
            <input type="text" name="address" id="address" value="{{ old('address', $profile->address) }}"
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('address')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="business_description" class="block text-sm font-medium text-gray-700 mb-1">事業内容</label>
            <p class="text-xs text-gray-500 mb-1">1行1項目で入力してください。</p>
            <textarea name="business_description" id="business_description" rows="4"
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('business_description', $profile->business_description) }}</textarea>
            @error('business_description')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="services" class="block text-sm font-medium text-gray-700 mb-1">主要運営サービス</label>
            <p class="text-xs text-gray-500 mb-1">1行1項目で入力してください。</p>
            <textarea name="services" id="services" rows="3"
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('services', $profile->services) }}</textarea>
            @error('services')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
            <input type="email" name="email" id="email" value="{{ old('email', $profile->email) }}"
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="business_hours" class="block text-sm font-medium text-gray-700 mb-1">営業時間</label>
            <textarea name="business_hours" id="business_hours" rows="2"
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('business_hours', $profile->business_hours) }}</textarea>
            @error('business_hours')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
    </form>
</div>
@endsection
