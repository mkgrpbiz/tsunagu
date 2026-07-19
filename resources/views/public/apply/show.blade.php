<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $project->name }} - TSUNAGU</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="max-w-md mx-auto px-4 py-8">
        @if ($result === 'unavailable')
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
                <p class="text-gray-600">この案件は現在ご紹介できません。</p>
            </div>

        @elseif ($result === 'friend')
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
                <p class="font-semibold mb-2">ご応募ありがとうございました。</p>
                <p class="text-gray-600 text-sm">LINEにて{{ $project->name }}のご案内をお送りしました。</p>
            </div>

        @elseif ($result === 'not_friend')
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
                <p class="font-semibold mb-2">ご応募ありがとうございました。</p>
                <p class="text-gray-600 text-sm mb-4">LINE公式アカウントを友だち追加すると、{{ $project->name }}のご案内が届きます。</p>
                @if ($officialAccountId)
                    <a href="https://line.me/R/ti/p/{{ $officialAccountId }}"
                       class="inline-block bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md px-4 py-2">
                        LINE登録して案内を受け取る
                    </a>
                @endif
            </div>

        @else
            @if ($project->image_path)
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-4">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($project->image_path) }}" alt="" class="w-full h-auto object-contain bg-gray-50">
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-4 text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form id="apply-form" method="POST" action="{{ route('apply.store', $inviteLink) }}"
                  class="bg-white border border-gray-200 rounded-lg p-6 space-y-4">
                @csrf
                <input type="hidden" name="line_uid" id="line_uid">
                <input type="hidden" name="line_display_name" id="line_display_name">
                <input type="hidden" name="is_friend" id="is_friend" value="0">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">案件名</label>
                    <input type="text" value="{{ $project->name }}" disabled
                           class="w-full rounded-md border border-gray-300 bg-gray-50 text-gray-500 shadow-sm">
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前</label>
                    <input type="text" name="name" id="name" required
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="name_kana" class="block text-sm font-medium text-gray-700 mb-1">フリガナ</label>
                    <input type="text" name="name_kana" id="name_kana" required
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
                    <input type="email" name="email" id="email" required
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                @if (! $liffId)
                    <div class="border border-amber-200 bg-amber-50 rounded-md p-3 text-xs text-amber-800 space-y-2">
                        <p>開発用モード（LIFF未設定のため手動入力）</p>
                        <div>
                            <label class="block mb-1">LINE User ID（テスト用）</label>
                            <input type="text" id="dev_line_uid" value="Udev0000000000000000000000000001"
                                   class="w-full rounded-md border-amber-300 text-xs">
                        </div>
                        <div>
                            <label class="block mb-1">LINE表示名（テスト用）</label>
                            <input type="text" id="dev_line_display_name" value="テストユーザー"
                                   class="w-full rounded-md border-amber-300 text-xs">
                        </div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="dev_is_friend">
                            友だち登録済みとして送信する
                        </label>
                    </div>
                @endif

                <button type="submit" id="apply-submit-button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md py-2">
                    案内を受け取る
                </button>
            </form>
        @endif
    </div>

    @if ($result === null)
        @if ($liffId)
            <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
            <script>
                var TSN_STORAGE_KEY = 'tsn_apply_form_{{ $inviteLink->token }}';

                function tsnFillFieldsFromStorage() {
                    var saved = sessionStorage.getItem(TSN_STORAGE_KEY);
                    if (!saved) return false;
                    sessionStorage.removeItem(TSN_STORAGE_KEY);
                    var data = JSON.parse(saved);
                    document.getElementById('name').value = data.name || '';
                    document.getElementById('name_kana').value = data.name_kana || '';
                    document.getElementById('email').value = data.email || '';
                    return true;
                }

                function tsnSetSubmitting(isSubmitting) {
                    var button = document.getElementById('apply-submit-button');
                    button.disabled = isSubmitting;
                    button.textContent = isSubmitting ? '送信中です...' : '案内を受け取る';
                }

                var resumingSubmit = tsnFillFieldsFromStorage();
                var liffReady = liff.init({ liffId: @json($liffId) });
                liffReady.catch((error) => console.error(error));

                document.getElementById('apply-form').addEventListener('submit', function (e) {
                    e.preventDefault();
                    tsnSetSubmitting(true);

                    liffReady
                        .then(() => {
                            if (!liff.isLoggedIn()) {
                                sessionStorage.setItem(TSN_STORAGE_KEY, JSON.stringify({
                                    name: document.getElementById('name').value,
                                    name_kana: document.getElementById('name_kana').value,
                                    email: document.getElementById('email').value,
                                }));
                                var from = encodeURIComponent(window.location.pathname);
                                window.location.href = 'https://liff.line.me/' + @json($liffId) + '?from=' + from;
                                return null;
                            }
                            return Promise.all([liff.getProfile(), liff.getFriendship()]);
                        })
                        .then((results) => {
                            if (!results) return;
                            const [profile, friendship] = results;
                            document.getElementById('line_uid').value = profile.userId;
                            document.getElementById('line_display_name').value = profile.displayName;
                            document.getElementById('is_friend').value = (friendship && friendship.friendFlag) ? '1' : '0';
                            document.getElementById('apply-form').submit();
                        })
                        .catch((error) => {
                            console.error(error);
                            tsnSetSubmitting(false);
                            alert('LINEとの連携に失敗しました。時間をおいて再度お試しください。');
                        });
                });

                if (resumingSubmit) {
                    document.getElementById('apply-form').requestSubmit();
                }
            </script>
        @else
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const devUid = document.getElementById('dev_line_uid');
                    const devName = document.getElementById('dev_line_display_name');
                    const devFriend = document.getElementById('dev_is_friend');

                    document.getElementById('line_uid').value = devUid.value;
                    document.getElementById('line_display_name').value = devName.value;
                    document.getElementById('is_friend').value = devFriend.checked ? '1' : '0';

                    document.getElementById('apply-form').addEventListener('submit', () => {
                        document.getElementById('line_uid').value = devUid.value;
                        document.getElementById('line_display_name').value = devName.value;
                        document.getElementById('is_friend').value = devFriend.checked ? '1' : '0';
                    });
                });
            </script>
        @endif
    @endif
</body>
</html>
