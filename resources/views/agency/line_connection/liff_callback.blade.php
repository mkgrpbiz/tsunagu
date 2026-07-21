<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LINE連携 - TSUNAGU</title>
</head>
<body style="font-family: sans-serif; padding: 32px 16px; text-align: center; color: #1f2937;">
    <p id="tsn-status">LINEと連携しています…しばらくお待ちください。</p>
    <p id="tsn-error" style="display:none; color: #b91c1c;">
        連携に失敗しました。時間をおいてマイページから再度お試しください。
    </p>

    <form id="tsn-connect-form" method="POST" action="{{ route('agency.line-connection.connect') }}">
        @csrf
        <input type="hidden" name="connect_token" value="{{ $connectToken }}">
        <input type="hidden" name="line_uid" id="tsn-line-uid">
        <input type="hidden" name="line_display_name" id="tsn-line-name">
    </form>

    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script>
    liff.init({ liffId: @json($liffId) })
        .then(function () {
            if (!liff.isLoggedIn()) {
                liff.login();
                return null;
            }
            return liff.getProfile();
        })
        .then(function (profile) {
            if (!profile) return;
            document.getElementById('tsn-line-uid').value = profile.userId;
            document.getElementById('tsn-line-name').value = profile.displayName;
            document.getElementById('tsn-connect-form').submit();
        })
        .catch(function (error) {
            console.error(error);
            document.getElementById('tsn-status').style.display = 'none';
            document.getElementById('tsn-error').style.display = 'block';
        });
    </script>
</body>
</html>
