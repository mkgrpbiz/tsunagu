@extends('layouts.agency')

@section('title', 'LINE連携が必要です')

@section('content')
<div class="bg-white border border-gray-200 rounded-lg p-12 text-center">
    <p class="text-lg font-semibold text-gray-800">🔒 パートナー専用LINEへの登録が必要です。</p>
    <div class="mt-6 flex justify-center">
        @include('partials.agency_line_connect_button')
    </div>
</div>
@endsection
