@extends('layouts.agency')

@section('title', 'ホーム')

@push('styles')
@include('partials.home_block_styles')
@endpush

@section('content')
@unless ($agency->line_uid)
    <div class="max-w-2xl mx-auto mb-4 rounded-md bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 text-sm text-center">
        <p class="font-semibold mb-1">📢 パートナー専用LINEに登録してください</p>
        <p class="mb-3">案件案内・重要なお知らせを受け取るため、LINE連携が必要です。</p>
        @include('partials.agency_line_connect_button', ['buttonClass' => 'inline-block bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md px-6 py-2'])
    </div>
@endunless
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
