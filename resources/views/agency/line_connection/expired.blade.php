<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LINE連携 - TSUNAGU</title>
</head>
<body style="font-family: sans-serif; padding: 32px 16px; text-align: center; color: #1f2937;">
    <p>LINE連携用のリンクの有効期限が切れました。お手数ですが、マイページから再度お試しください。</p>
    <button type="button" id="tsn-close-button" style="border: none; border-radius: 8px; padding: 10px 24px; font-weight: 700; cursor: pointer; background: #111827; color: #fff;">
        閉じる
    </button>
</body>

<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
<script>
document.getElementById('tsn-close-button').addEventListener('click', function () {
    liff.init({ liffId: @json($liffId) })
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
</html>
