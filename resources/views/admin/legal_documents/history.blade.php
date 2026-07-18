@extends('layouts.admin')

@section('title', $type->label().' 履歴')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">{{ $type->label() }} 履歴</h1>
    <a href="{{ route('admin.legal-documents.index') }}" class="text-sm text-gray-500 hover:underline">契約管理に戻る</a>
</div>

@forelse ($documents as $document)
    @php
        $statusColor = match ($document->status) {
            \App\Enums\LegalDocumentStatus::Published => 'bg-green-50 text-green-700 border-green-200',
            \App\Enums\LegalDocumentStatus::Draft => 'bg-gray-50 text-gray-600 border-gray-200',
            \App\Enums\LegalDocumentStatus::Unpublished => 'bg-gray-100 text-gray-500 border-gray-200',
        };
    @endphp
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-4">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <span class="font-semibold">バージョン {{ $document->version }}</span>
                <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $statusColor }}">{{ $document->status->label() }}</span>
            </div>
            <span class="text-xs text-gray-500">更新: {{ $document->created_at->format('Y-m-d H:i') }}（{{ $document->createdByUser?->name ?? '—' }}）</span>
        </div>
        <dl class="grid grid-cols-2 gap-4 text-sm mb-4">
            <div>
                <dt class="text-gray-500">施行日</dt>
                <dd>{{ $document->effective_date->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">公開日時</dt>
                <dd>{{ optional($document->published_at)->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
            @if ($document->change_notes)
                <div class="col-span-2">
                    <dt class="text-gray-500">更新内容</dt>
                    <dd class="whitespace-pre-line">{{ $document->change_notes }}</dd>
                </div>
            @endif
        </dl>
        <details>
            <summary class="text-sm text-blue-600 cursor-pointer">本文を見る</summary>
            <div class="mt-2 text-sm text-gray-700 leading-relaxed" style="white-space: pre-line">{{ $document->body }}</div>
        </details>
    </div>
@empty
    <p class="text-gray-400 text-center py-10">履歴がありません。</p>
@endforelse
@endsection
