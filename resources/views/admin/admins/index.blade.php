@extends('layouts.admin')

@section('title', '管理者管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">管理者管理</h1>
    <a href="{{ route('admin.admins.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">＋ 追加</a>
</div>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">名前</th>
                <th class="px-4 py-3 font-medium">メールアドレス</th>
                <th class="px-4 py-3 font-medium">役割</th>
                <th class="px-4 py-3 font-medium">閲覧可能メニュー</th>
                <th class="px-4 py-3 font-medium w-64"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($admins as $adm)
                <tr>
                    <td class="px-4 py-3">
                        {{ $adm->name }}
                        @if ($adm->id === auth()->id())
                            <span class="text-xs text-blue-600 ml-1">（自分）</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $adm->email }}</td>
                    <td class="px-4 py-3">
                        @if ($adm->isAdmin())
                            <span class="text-xs font-medium border rounded-full px-2 py-1 bg-blue-50 text-blue-700 border-blue-200">管理者</span>
                        @else
                            <span class="text-xs font-medium border rounded-full px-2 py-1 bg-gray-50 text-gray-600 border-gray-200">運用担当</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        @if ($adm->isAdmin())
                            <span class="text-green-600">全メニュー</span>
                        @else
                            @php
                                $keys = \App\Http\Controllers\Admin\AdminManagerController::menuKeys();
                                $labels = collect($adm->accessible_menus ?? [])->map(fn ($k) => $keys[$k] ?? $k);
                            @endphp
                            {{ $labels->join('、') ?: '（なし）' }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex gap-2 justify-end">
                            <a href="{{ route('admin.admins.edit', $adm) }}" class="text-xs bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded">編集</a>
                            <form method="POST" action="{{ route('admin.admins.reset-password', $adm) }}">
                                @csrf
                                <button type="submit" onclick="return confirm('パスワードを pass1234 にリセットしますか？');"
                                        class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded">PWリセット</button>
                            </form>
                            @if ($adm->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.admins.destroy', $adm) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('削除しますか？');"
                                            class="text-xs bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">削除</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">管理者がいません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
