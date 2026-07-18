@extends('layouts.admin')

@section('title', '契約管理')

@section('content')
<h1 class="text-xl font-semibold mb-6">契約管理</h1>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">文書名</th>
                <th class="px-4 py-3 font-medium">バージョン</th>
                <th class="px-4 py-3 font-medium">公開状態</th>
                <th class="px-4 py-3 font-medium">施行日</th>
                <th class="px-4 py-3 font-medium">最終更新日時</th>
                <th class="px-4 py-3 font-medium">更新者</th>
                <th class="px-4 py-3 font-medium w-32"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($documents as $row)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $row['type']->label() }}</td>
                    <td class="px-4 py-3">{{ $row['latest']?->version ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if ($row['latest'])
                            @php
                                $statusColor = match ($row['latest']->status) {
                                    \App\Enums\LegalDocumentStatus::Published => 'bg-green-50 text-green-700 border-green-200',
                                    \App\Enums\LegalDocumentStatus::Draft => 'bg-gray-50 text-gray-600 border-gray-200',
                                    \App\Enums\LegalDocumentStatus::Unpublished => 'bg-gray-100 text-gray-500 border-gray-200',
                                };
                            @endphp
                            <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $statusColor }}">{{ $row['latest']->status->label() }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $row['latest']?->effective_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $row['latest']?->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $row['latest']?->createdByUser?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <a href="{{ route('admin.legal-documents.edit', $row['type']->value) }}" class="text-blue-600 hover:underline">編集</a>
                        <a href="{{ route('admin.legal-documents.history', $row['type']->value) }}" class="text-gray-500 hover:underline">履歴</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
