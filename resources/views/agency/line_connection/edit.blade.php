@extends('layouts.agency')

@section('title', 'LINE通知設定')

@section('content')
<h1 class="text-xl font-semibold mb-4">LINE通知設定</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    @if ($agency->line_uid)
        <p class="text-sm text-gray-700 mb-4">
            LINE連携済みです（{{ $agency->line_display_name ?: 'LINEアカウント' }}）。
        </p>

        <form method="POST" action="{{ route('agency.line-connection.preferences.update') }}" class="mb-6">
            @csrf
            @method('PUT')

            <div class="mb-4 border border-gray-200 rounded-md p-4 bg-gray-50">
                <label class="flex items-center gap-2">
                    <input type="checkbox" checked disabled class="opacity-60">
                    <span class="text-sm font-medium text-gray-700">🔒 重要なお知らせ（OFF不可）</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">審査結果・メンテナンス・規約等の重要なお知らせ</p>
            </div>

            <div class="mb-4 border border-gray-200 rounded-md p-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="line_notify_project_info" value="1" {{ $agency->line_notify_project_info ? 'checked' : '' }}>
                    <span class="text-sm font-medium text-gray-700">案件情報</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">新案件追加・案件停止・単価変更など</p>
            </div>

            <div class="mb-4 border border-gray-200 rounded-md p-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="line_notify_payment" value="1" {{ $agency->line_notify_payment ? 'checked' : '' }}>
                    <span class="text-sm font-medium text-gray-700">振込通知</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">お振込み完了</p>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">
                保存
            </button>
        </form>

        <form method="POST" action="{{ route('agency.line-connection.destroy') }}" onsubmit="return confirm('LINE連携を解除しますか？解除するとサービスの一部機能がご利用いただけなくなります。');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md px-4 py-2">
                連携を解除する
            </button>
        </form>
    @else
        <p class="text-sm text-gray-700 mb-4">
            案件案内・審査結果・出金明細などをLINEでお知らせするため、LINE連携をお願いします。
        </p>
        @include('partials.agency_line_connect_button')
    @endif
</div>
@endsection
