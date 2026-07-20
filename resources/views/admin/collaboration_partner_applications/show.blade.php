@extends('layouts.admin')

@section('title', '共創パートナー申請 詳細')

@section('content')
@php
    $isHandled = $application->status === \App\Enums\CollaborationPartnerApplicationStatus::Handled;
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
            <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $isHandled ? 'bg-green-50 text-green-700 border-green-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
                {{ $application->status->label() }}
            </span>
            <form method="POST" action="{{ route('admin.collaboration-partner-applications.toggle-status', $application) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">
                    {{ $isHandled ? '未対応に戻す' : '対応済にする' }}
                </button>
            </form>
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
