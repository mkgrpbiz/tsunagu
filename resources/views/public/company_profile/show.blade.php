@extends('layouts.public')

@section('title', '会社概要')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white border border-gray-200 rounded-lg p-8">
        <h1 class="text-lg font-semibold mb-6">会社概要</h1>

        @if ($profile->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($profile->logo_path) }}" alt="{{ $profile->company_name }}" class="h-16 mb-6">
        @endif

        <dl class="divide-y divide-gray-100 text-sm">
            <div class="py-3 grid grid-cols-3 gap-4">
                <dt class="text-gray-500">会社名</dt>
                <dd class="col-span-2 text-gray-900">{{ $profile->company_name }}</dd>
            </div>
            @if ($profile->representative_name)
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-gray-500">代表者</dt>
                    <dd class="col-span-2 text-gray-900">{{ $profile->representative_title }}　{{ $profile->representative_name }}</dd>
                </div>
            @endif
            @if ($profile->address)
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-gray-500">所在地</dt>
                    <dd class="col-span-2 text-gray-900">{{ $profile->address }}</dd>
                </div>
            @endif
            @if ($profile->business_description)
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-gray-500">事業内容</dt>
                    <dd class="col-span-2 text-gray-900" style="white-space: pre-line">{{ $profile->business_description }}</dd>
                </div>
            @endif
            @if ($profile->services)
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-gray-500">主要運営サービス</dt>
                    <dd class="col-span-2 text-gray-900" style="white-space: pre-line">{{ $profile->services }}</dd>
                </div>
            @endif
            @if ($profile->email)
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-gray-500">メールアドレス</dt>
                    <dd class="col-span-2 text-gray-900">{{ $profile->email }}</dd>
                </div>
            @endif
            @if ($profile->business_hours)
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-gray-500">営業時間</dt>
                    <dd class="col-span-2 text-gray-900" style="white-space: pre-line">{{ $profile->business_hours }}</dd>
                </div>
            @endif
        </dl>
    </div>
</div>
@endsection
