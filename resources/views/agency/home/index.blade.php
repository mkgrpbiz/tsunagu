@extends('layouts.agency')

@section('title', 'ホーム')

@push('styles')
@include('partials.home_block_styles')
@endpush

@section('content')
@php
    $bannerMessages = [
        'pending_review' => ['title' => '⏳ 審査中です', 'body' => '運営による審査完了後、案件一覧・各種機能をご利用いただけます。'],
        'consent_required' => ['title' => '📄 契約書類へのご同意が必要です', 'body' => '追加情報のご入力より契約書類にご同意いただくと、各種機能をご利用いただけます。'],
        'line_required' => ['title' => '📢 パートナー専用LINEに登録してください', 'body' => '案件案内・重要なお知らせを受け取るため、LINE連携が必要です。'],
    ];
@endphp
@if ($bannerReason)
    <div class="max-w-2xl mx-auto mb-4 rounded-md bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 text-sm text-center">
        <p class="font-semibold mb-1">{{ $bannerMessages[$bannerReason]['title'] }}</p>
        <p class="mb-3">{{ $bannerMessages[$bannerReason]['body'] }}</p>
        @if ($bannerReason === 'consent_required')
            <a href="{{ route('agency.additional-info.edit') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-6 py-2">追加情報を入力する</a>
        @elseif ($bannerReason === 'line_required')
            @include('partials.agency_line_connect_button', ['buttonClass' => 'inline-block bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md px-6 py-2'])
        @endif
    </div>
@endif
<div class="tsn-home">
    <div class="hero">
        @if ($content->brand_logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($content->brand_logo_path) }}" alt="TSUNAGU Partner Network" class="brand-logo">
        @else
            <div class="brand">TSUNAGU <span>Partner Network</span></div>
        @endif
        <div class="tagline">{{ $content->hero_tagline }}</div>
        <div class="welcome">ようこそ、{{ $agency->name }} 様</div>
    </div>

    @foreach ($blocks as $block)
        <div class="block">
            @include('partials.home_block', compact('block', 'agency', 'referralUrl', 'salesMaterials', 'announcements', 'restrictedReason'))
        </div>
    @endforeach

    <div class="closing">{{ $content->closing_message }}</div>
</div>
@endsection
