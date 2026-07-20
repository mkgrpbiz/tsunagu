@extends('layouts.admin')

@section('title', '共創パートナー申請 詳細')

@section('content')
@php
    $statusColor = match ($application->status) {
        \App\Enums\CollaborationPartnerApplicationStatus::Approved => 'bg-green-50 text-green-700 border-green-200',
        \App\Enums\CollaborationPartnerApplicationStatus::Rejected => 'bg-red-50 text-red-700 border-red-200',
        \App\Enums\CollaborationPartnerApplicationStatus::Pending => 'bg-amber-50 text-amber-700 border-amber-200',
    };
@endphp

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">共創パートナー申請 詳細</h1>
    <a href="{{ route('admin.collaboration-partner-applications.index') }}" class="text-sm text-blue-600 hover:underline">一覧に戻る</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-2xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <p class="text-xs text-gray-500">申請日時: {{ $application->created_at->format('Y-m-d H:i') }}</p>
            <p class="text-sm font-semibold mt-1">申請元パートナー: {{ $application->agency->name }}（{{ $application->agency->referral_code }}）</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $statusColor }}">
                {{ $application->status->label() }}
            </span>
            <div class="flex gap-1">
                @foreach (\App\Enums\CollaborationPartnerApplicationStatus::cases() as $statusOption)
                    @continue($statusOption === $application->status)
                    <form method="POST" action="{{ route('admin.collaboration-partner-applications.update-status', $application) }}">
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
            <dt class="text-gray-500">共創したい内容</dt>
            <dd class="mt-0.5 whitespace-pre-line">{{ $application->collaboration_content }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-gray-500">具体的な提案内容</dt>
            <dd class="mt-0.5 whitespace-pre-line">{{ $application->proposal_details }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-gray-500">期待する役割・協力内容</dt>
            <dd class="mt-0.5 whitespace-pre-line">{{ $application->expected_role }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-gray-500">参考資料・URL</dt>
            <dd class="mt-0.5 whitespace-pre-line">
                @if ($application->reference_url)
                    <a href="{{ $application->reference_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ $application->reference_url }}</a>
                @else
                    （未入力）
                @endif
            </dd>
        </div>
    </dl>
</div>
@endsection
