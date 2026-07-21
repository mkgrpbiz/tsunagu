@extends('layouts.agency')

@section('title', 'LINE通知設定')

@section('content')
<h1 class="text-xl font-semibold mb-4">LINE通知設定</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    @if ($agency->line_uid)
        <p class="text-sm text-gray-700 mb-4">
            LINE連携済みです（{{ $agency->line_display_name ?: 'LINEアカウント' }}）。<br>
            案件案内・審査結果・出金明細などをLINEでお知らせします。
        </p>
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
