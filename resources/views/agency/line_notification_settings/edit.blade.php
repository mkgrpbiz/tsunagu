@extends('layouts.agency')

@section('title', 'LINE通知設定')

@section('content')
<h1 class="text-xl font-semibold mb-4">LINE通知設定</h1>

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    @if ($agency->line_uid)
        <p class="text-sm text-gray-700 mb-4">
            LINE連携済みです（{{ $agency->line_display_name ?: 'LINEアカウント' }}）。<br>
            共創先紹介・共創パートナー申請の審査結果をLINEでお知らせします。
        </p>
        <form method="POST" action="{{ route('agency.line-notification-settings.destroy') }}" onsubmit="return confirm('LINE連携を解除しますか？');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md px-4 py-2">
                連携を解除する
            </button>
        </form>
    @else
        <p class="text-sm text-gray-700 mb-4">
            LINEと連携すると、共創先紹介・共創パートナー申請の審査結果（承認・見送り）をLINEでお知らせします。
        </p>

        <form id="line-connect-form" method="POST" action="{{ route('agency.line-notification-settings.update') }}">
            @csrf
            <input type="hidden" name="line_uid" id="line_uid">
            <input type="hidden" name="line_display_name" id="line_display_name">
        </form>

        <button type="button" id="line-connect-button" class="bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md px-4 py-2">
            LINEと連携する
        </button>
    @endif
</div>

@if (! $agency->line_uid)
    @if ($liffId)
        <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
        <script>
            function tsnLineSettingsResume() {
                var params = new URLSearchParams(window.location.search);
                if (!params.has('tsn_resume')) return false;

                var nonce = params.get('tsn_nonce');
                var usedKey = nonce ? ('tsn_line_settings_used_' + nonce) : null;

                if (usedKey && localStorage.getItem(usedKey)) {
                    return false;
                }

                if (usedKey) {
                    localStorage.setItem(usedKey, '1');
                }

                return true;
            }

            var resuming = tsnLineSettingsResume();
            var liffReady = liff.init({ liffId: @json($liffId) });
            liffReady.catch((error) => console.error(error));

            function tsnConnectLine() {
                liffReady
                    .then(() => {
                        if (!liff.isLoggedIn()) {
                            var resumeParams = new URLSearchParams();
                            resumeParams.set('tsn_resume', '1');
                            resumeParams.set('tsn_nonce', Date.now().toString(36) + Math.random().toString(36).slice(2));
                            var from = encodeURIComponent(window.location.pathname + '?' + resumeParams.toString());
                            window.location.href = 'https://liff.line.me/' + @json($liffId) + '?from=' + from;
                            return null;
                        }
                        return liff.getProfile();
                    })
                    .then((profile) => {
                        if (!profile) return;
                        document.getElementById('line_uid').value = profile.userId;
                        document.getElementById('line_display_name').value = profile.displayName;
                        document.getElementById('line-connect-form').submit();
                    })
                    .catch((error) => {
                        console.error(error);
                        alert('LINEとの連携に失敗しました。時間をおいて再度お試しください。');
                    });
            }

            document.getElementById('line-connect-button').addEventListener('click', tsnConnectLine);

            if (resuming) {
                tsnConnectLine();
            }
        </script>
    @endif
@endif
@endsection
