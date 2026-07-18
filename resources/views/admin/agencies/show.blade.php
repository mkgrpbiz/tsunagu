@extends('layouts.admin')

@section('title', $agency->name.' 詳細')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">{{ $agency->name }}</h1>
    <a href="{{ route('admin.agencies.index') }}" class="text-sm text-gray-500 hover:underline">一覧に戻る</a>
</div>

<div class="grid md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">基本情報</h2>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-gray-500">名前</dt><dd>{{ $agency->name }}</dd></div>
            <div><dt class="text-gray-500">フリガナ</dt><dd>{{ $agency->name_kana }}</dd></div>
            <div><dt class="text-gray-500">性別</dt><dd>{{ $agency->gender?->label() }}</dd></div>
            <div><dt class="text-gray-500">都道府県</dt><dd>{{ $agency->prefecture }}</dd></div>
            <div><dt class="text-gray-500">職業</dt><dd>{{ $agency->occupation ?: '—' }}</dd></div>
            <div><dt class="text-gray-500">電話番号</dt><dd>{{ $agency->phone }}</dd></div>
            <div class="col-span-2"><dt class="text-gray-500">メールアドレス</dt><dd>{{ $agency->email }}</dd></div>
            <div><dt class="text-gray-500">紹介者コード</dt><dd>{{ $agency->referredBy?->referral_code ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">登録日時</dt><dd>{{ $agency->created_at->format('Y-m-d H:i') }}</dd></div>
        </dl>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">活動情報</h2>
        <dl class="text-sm space-y-3">
            <div><dt class="text-gray-500">活動区分</dt><dd>{{ $agency->activity_type?->label() ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">希望する活動内容</dt><dd>{{ $agency->desired_activities ? implode('、', $agency->desired_activities) : '—' }}</dd></div>
            <div><dt class="text-gray-500">現在の活動内容</dt><dd class="whitespace-pre-line">{{ $agency->current_activity ?: '—' }}</dd></div>
            <div><dt class="text-gray-500">実績</dt><dd class="whitespace-pre-line">{{ $agency->track_record ?: '—' }}</dd></div>
            <div>
                <dt class="text-gray-500">媒体URL</dt>
                <dd>
                    @php $urls = array_filter(array_map('trim', explode("\n", (string) $agency->media_urls))); @endphp
                    @forelse ($urls as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="block text-blue-600 hover:underline break-all">{{ $url }}</a>
                    @empty
                        —
                    @endforelse
                </dd>
            </div>
            <div><dt class="text-gray-500">自己PR</dt><dd class="whitespace-pre-line">{{ $agency->self_pr ?: '—' }}</dd></div>
        </dl>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">審査操作</h2>

    <dl class="grid grid-cols-4 gap-3 text-sm mb-4">
        <div><dt class="text-gray-500">現在のステータス</dt><dd><span class="text-xs font-medium border rounded-full px-2 py-1 {{ $agency->status->color() }}">{{ $agency->status->label() }}</span></dd></div>
        <div><dt class="text-gray-500">承認日時</dt><dd>{{ optional($agency->approved_at)->format('Y-m-d H:i') ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">承認者</dt><dd>{{ $agency->approvedByUser?->name ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">最終ログイン日時</dt><dd>{{ optional($agency->last_login_at)->format('Y-m-d H:i') ?? '—' }}</dd></div>
    </dl>

    <form method="POST" action="{{ route('admin.agencies.update-status', $agency) }}">
        @csrf
        @method('PATCH')

        <div class="mb-4">
            <label for="review_note" class="block text-sm font-medium text-gray-700 mb-1">審査メモ（管理者専用）</label>
            <textarea name="review_note" id="review_note" rows="3"
                      class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('review_note', $agency->review_note) }}</textarea>
        </div>

        <div class="flex gap-3">
            @if ($agency->status !== \App\Enums\AgencyStatus::Approved)
                <button type="submit" name="status" value="approved" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md px-4 py-2">承認する</button>
            @endif
            @if ($agency->status !== \App\Enums\AgencyStatus::Rejected)
                <button type="submit" name="status" value="rejected" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md px-4 py-2">否認する</button>
            @endif
            @if ($agency->status !== \App\Enums\AgencyStatus::Suspended)
                <button type="submit" name="status" value="suspended" class="bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md px-4 py-2">利用停止にする</button>
            @endif
            @if ($agency->status !== \App\Enums\AgencyStatus::Pending)
                <button type="submit" name="status" value="pending" class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-md px-4 py-2">審査中へ戻す</button>
            @endif
        </div>
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">パートナー区分</h2>
    <div class="flex items-center justify-between">
        <div>
            <span class="text-xs font-medium border rounded-full px-2 py-1 {{ $agency->is_collaboration_partner ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-gray-50 text-gray-500 border-gray-200' }}">
                {{ $agency->is_collaboration_partner ? '共創パートナー' : '通常パートナー' }}
            </span>
            @if ($agency->is_collaboration_partner)
                <span class="text-xs text-gray-500 ml-2">{{ $agency->collaboration_partner_at->format('Y-m-d H:i') }} に指定</span>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.agencies.toggle-collaboration-partner', $agency) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="text-sm font-medium rounded-md px-4 py-2 {{ $agency->is_collaboration_partner ? 'bg-gray-500 hover:bg-gray-600 text-white' : 'bg-purple-600 hover:bg-purple-700 text-white' }}">
                {{ $agency->is_collaboration_partner ? '共創パートナーの指定を解除' : '共創パートナーにする' }}
            </button>
        </form>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">契約・同意情報</h2>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-3 py-2 font-medium">文書</th>
                <th class="px-3 py-2 font-medium">バージョン</th>
                <th class="px-3 py-2 font-medium">同意日時</th>
                <th class="px-3 py-2 font-medium">IPアドレス</th>
                <th class="px-3 py-2 font-medium">User-Agent</th>
                <th class="px-3 py-2 font-medium">同意方法</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach (\App\Enums\LegalDocumentType::cases() as $type)
                @php $consent = $consents->get($type->value); @endphp
                <tr>
                    <td class="px-3 py-2">{{ $type->label() }}</td>
                    <td class="px-3 py-2">{{ $consent?->legalDocument?->version ?? '—' }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ optional($consent?->consented_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $consent?->ip_address ?? '—' }}</td>
                    <td class="px-3 py-2 max-w-xs truncate" title="{{ $consent?->user_agent }}">{{ $consent?->user_agent ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $consent?->method ?? '記録なし' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="bg-white border border-gray-200 rounded-lg p-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">ステータス変更履歴</h2>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-3 py-2 font-medium">変更日時</th>
                <th class="px-3 py-2 font-medium">変更前</th>
                <th class="px-3 py-2 font-medium">変更後</th>
                <th class="px-3 py-2 font-medium">変更者</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($statusHistories as $history)
                <tr>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $history->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-3 py-2">{{ $history->from_status?->label() ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $history->to_status->label() }}</td>
                    <td class="px-3 py-2">{{ $history->changedByUser?->name ?? '（自己登録）' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-3 py-6 text-center text-gray-400">履歴がありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
