@extends('layouts.public')

@section('title', 'パートナー募集')

@push('styles')
@include('partials.home_block_styles')
<style>
.lp-wrap{max-width:24rem;margin:0 auto;padding:2.5rem 1.25rem 4rem;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Hiragino Sans",Meiryo,sans-serif}
.lp-header{text-align:center}
.lp-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(37,99,235,.06);color:#2563eb;font-size:10px;font-weight:700;padding:3px 12px;border-radius:999px;margin-bottom:14px;border:1px solid rgba(37,99,235,.15)}
.lp-brand{font-weight:900;font-size:20px;color:#0f172a}
.lp-brand .lp-brand-main{font-size:32px}
.lp-brand .lp-brand-sub{color:#2563eb}
.lp-brand-logo{display:block;max-height:192px;max-width:100%;margin:0 auto}
.lp-tagline{margin-top:4px;font-size:12px;color:#9ca3af}
.lp-hero{margin-top:24px;text-align:center}
.lp-hero h1{font-size:22px;font-weight:900;color:#111827;line-height:1.4}
.lp-hero h1 span{color:#2563eb}
.lp-hero p{margin-top:10px;font-size:13px;color:#6b7280;line-height:1.7}
.lp-steps{margin-top:24px;background:#eff6ff;border-radius:20px;padding:20px}
.lp-steps-title{font-size:12px;font-weight:800;color:#2563eb;text-align:center;margin-bottom:14px}
.lp-step{display:flex;align-items:center;gap:12px;margin:10px 0;font-size:13px;color:#374151}
.lp-step .num{flex-shrink:0;width:24px;height:24px;border-radius:999px;background:#2563eb;color:#fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center}
.lp-cta{display:block;width:100%;background:#2563eb;color:#fff;font-weight:800;font-size:16px;padding:16px;border-radius:16px;text-align:center;margin-top:28px;box-shadow:0 10px 24px rgba(37,99,235,.25);transition:transform .1s}
.lp-cta:active{transform:scale(.97)}
.lp-footer{margin-top:24px;text-align:center;font-size:11px;color:#d1d5db}
.lp-wrap .tsn-home{max-width:none;margin:24px 0 0}
.lp-wrap .tsn-home .benefits-title{font-size:14px}
</style>
@endpush

@section('content')
<div class="lp-wrap">
    <div class="lp-header">
        @if ($lpContent->brand_badge_text)
            <div class="lp-badge">{{ $lpContent->brand_badge_text }}</div>
        @endif

        @if ($homeContent->brand_logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($homeContent->brand_logo_path) }}" alt="TSUNAGU Partner Network" class="lp-brand-logo">
        @else
            <div class="lp-brand"><span class="lp-brand-main">TSUNAGU</span><br><span class="lp-brand-sub">Partner Network</span></div>
        @endif

        <div class="lp-tagline">{{ $lpContent->tagline }}</div>
    </div>

    <div class="lp-hero">
        <h1>{{ $lpContent->hero_line1 }}<br><span>{{ $lpContent->hero_highlight }}</span>{{ $lpContent->hero_suffix }}</h1>
    </div>

    @if ($benefitsBlock)
        <div class="tsn-home">
            @include('partials.home_block', ['block' => $benefitsBlock])
        </div>
    @endif

    <div class="lp-steps">
        <div class="lp-steps-title">{{ $lpContent->steps_title }}</div>
        <div class="lp-step"><span class="num">1</span> {{ $lpContent->step1 }}</div>
        <div class="lp-step"><span class="num">2</span> {{ $lpContent->step2 }}</div>
        <div class="lp-step"><span class="num">3</span> {{ $lpContent->step3 }}</div>
    </div>

    <a class="lp-cta" href="{{ route('agency.register.form', $referralCode ? ['ref' => $referralCode] : []) }}">
        {{ $lpContent->cta_text }}
    </a>

    <p class="text-center text-sm text-gray-500 mt-6">
        すでに登録済みの方は
        <a href="{{ route('agency.login') }}" class="text-blue-600 hover:underline">ログインはこちら</a>
    </p>

    @if ($referralCode)
        <div class="lp-footer">紹介コード: {{ $referralCode }}</div>
    @endif
</div>
@endsection
