@extends('layouts.public')

@section('title', 'ログイン完了')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="max-w-sm w-full bg-white border border-gray-200 rounded-lg p-6 text-center">
        <p class="font-semibold mb-2">LINEログインが完了しました。</p>
        <p class="text-sm text-gray-600 mb-4">元の画面に戻り、お手続きを続けてください。</p>
        <button type="button" id="tsn-back-button" class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-6 py-2">
            戻る
        </button>
    </div>
</div>

<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
<script>
document.getElementById('tsn-back-button').addEventListener('click', function () {
    liff.init({ liffId: @json(config('services.line_customer.liff_id')) })
        .then(function () {
            if (liff.isInClient()) {
                liff.closeWindow();
            } else {
                history.back();
            }
        })
        .catch(function () {
            history.back();
        });
});
</script>
@endsection
