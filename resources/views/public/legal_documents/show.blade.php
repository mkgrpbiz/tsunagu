@extends('layouts.public')

@section('title', $document->title)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white border border-gray-200 rounded-lg p-8">
        <h1 class="text-lg font-semibold">{{ $document->title }}</h1>
        <p class="text-xs text-gray-500 mt-1">バージョン {{ $document->version }}（施行日: {{ $document->effective_date->format('Y-m-d') }}）</p>

        <div class="mt-6 text-sm text-gray-700 leading-relaxed" style="white-space: pre-line">{{ $document->body }}</div>
    </div>
</div>
@endsection
