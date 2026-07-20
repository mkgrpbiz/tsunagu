@extends('layouts.admin')

@section('title', '共創パートナー紹介 詳細')

@section('content')
@php
    $statusColor = match ($referral->status) {
        \App\Enums\CollaborationReferralStatus::Approved => 'bg-green-50 text-green-700 border-green-200',
        \App\Enums\CollaborationReferralStatus::Rejected => 'bg-red-50 text-red-700 border-red-200',
        \App\Enums\CollaborationReferralStatus::Pending => 'bg-amber-50 text-amber-700 border-amber-200',
    };
@endphp

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">共創パートナー紹介 詳細</h1>
    <a href="{{ route('admin.collaboration-referrals.index') }}" class="text-sm text-blue-600 hover:underline">一覧に戻る</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-2xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <p class="text-xs text-gray-500">申請日時: {{ $referral->created_at->format('Y-m-d H:i') }}</p>
            <p class="text-sm font-semibold mt-1">紹介元パートナー: {{ $referral->agency->name }}（{{ $referral->agency->referral_code }}）</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $statusColor }}">
                {{ $referral->status->label() }}
            </span>
            <div class="flex gap-1">
                @foreach (\App\Enums\CollaborationReferralStatus::cases() as $statusOption)
                    @continue($statusOption === $referral->status)
                    <form method="POST" action="{{ route('admin.collaboration-referrals.update-status', $referral) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $statusOption->value }}">
                        <button type="submit" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">
                            {{ $statusOption->label() }}にする
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>

    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
        <div class="col-span-2">
            <dt class="text-gray-500">紹介したい方のお名前</dt>
            <dd class="mt-0.5">{{ $referral->referred_name }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-gray-500">会社名</dt>
            <dd class="mt-0.5">{{ $referral->referred_company ?: '（未入力）' }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-gray-500">現在されている事業・職業</dt>
            <dd class="mt-0.5 whitespace-pre-line">{{ $referral->referred_business }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-gray-500">過去の取引実績・実績</dt>
            <dd class="mt-0.5 whitespace-pre-line">{{ $referral->referred_track_record }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-gray-500">紹介理由</dt>
            <dd class="mt-0.5 whitespace-pre-line">{{ $referral->reason }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-gray-500">ご本人へ紹介の了承</dt>
            <dd class="mt-0.5">{{ $referral->consent_obtained ? 'はい' : 'いいえ' }}</dd>
        </div>
    </dl>
</div>
@endsection
