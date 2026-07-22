@extends('layouts.agency')

@section('title', '審査中')

@section('content')
@php
    $messages = [
        'pending' => ['審査中です', '運営による審査完了後、案件一覧など各種機能をご利用いただけます。'],
        'rejected' => ['ご利用いただけません', '審査の結果、今回はパートナー登録が承認されませんでした。'],
        'suspended' => ['ご利用を停止しております', '詳細については運営までお問い合わせください。'],
    ];
    [$heading, $body] = $messages[$agency->status->value] ?? $messages['pending'];
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-12 text-center">
    <p class="text-lg font-semibold text-gray-800">{{ $heading }}</p>
    <p class="text-sm text-gray-500 mt-2">{{ $body }}</p>

    @if ($agency->status->value === 'pending')
        <p class="text-sm text-gray-500 mt-4">サービスのご利用にはLINE連携が必要です。審査結果もLINEにてお知らせしますので、連携をお願いします。</p>
        <div class="mt-6">
            @if ($agency->line_uid)
                <p class="text-sm font-medium text-green-700">✅ LINE連携済みです</p>
            @else
                @include('partials.agency_line_connect_button', ['agency' => $agency, 'liffId' => $liffId ?? null, 'buttonClass' => 'inline-block bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md px-6 py-2'])
            @endif
        </div>
    @endif
</div>
@endsection
