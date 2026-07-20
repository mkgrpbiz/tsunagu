@extends('layouts.agency')

@section('title', '共創先紹介を受け付けました')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white border border-gray-200 rounded-lg p-8 text-center">
        <h1 class="text-lg font-semibold mb-4">共創先紹介を受け付けました</h1>
        <p class="text-sm text-gray-700 leading-relaxed mb-1">ご紹介ありがとうございます。</p>
        <p class="text-sm text-gray-700 leading-relaxed mb-6">ご紹介内容を確認・審査のうえ、必要に応じて1〜3営業日以内にLINEにてご連絡いたします。</p>
        <a href="{{ route('agency.home') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-6 py-2">
            マイページへ戻る
        </a>
    </div>
</div>
@endsection
