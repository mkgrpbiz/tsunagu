@extends('layouts.public')

@section('title', $document->title)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white border border-gray-200 rounded-lg p-8">
        <h1 class="text-lg font-semibold">{{ $document->title }}</h1>
        <p class="text-xs text-gray-500 mt-1">バージョン {{ $document->version }}（施行日: {{ $document->effective_date->format('Y-m-d') }}）</p>

        @if ($document->change_notes)
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-md p-4 text-sm text-blue-800" style="white-space: pre-line">
                <p class="font-semibold mb-1">今回の変更点</p>
                {{ $document->change_notes }}
            </div>
        @endif

        <div class="mt-6 text-sm text-gray-700 leading-relaxed" style="white-space: pre-line">{{ $document->body }}</div>
    </div>
</div>
@endsection
