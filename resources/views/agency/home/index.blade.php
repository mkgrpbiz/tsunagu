@extends('layouts.agency')

@section('title', 'ホーム')

@push('styles')
@include('partials.home_block_styles')
@endpush

@section('content')
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
