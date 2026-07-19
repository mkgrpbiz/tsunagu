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

            <div id="loading" class="bg-white border border-gray-200 rounded-lg p-6 text-center text-sm text-gray-500">
                読み込み中です...
            </div>

            <form id="apply-form" method="POST" action="{{ route('apply.store', $inviteLink) }}"
                  class="bg-white border border-gray-200 rounded-lg p-6 space-y-4 hidden">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">LINE名</label>
                    <input type="text" id="line_name_display" value="" disabled
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

                <div id="line-login-prompt" class="border border-blue-200 bg-blue-50 rounded-md p-3 text-xs text-blue-800 space-y-2 hidden">
                    <p>LINEとの連携が完了していません。下のボタンからログインしてください。</p>
                    <button type="button" id="line-login-button" class="w-full bg-green-500 hover:bg-green-600 text-white font-medium rounded-md py-2">
                        LINEでログイン
                    </button>
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

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md py-2">
                    案内を受け取る
                </button>
            </form>
        @endif
    </div>

    @if ($result === null)
        @if ($liffId)
            <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
            <script>
                function tsnShowForm() {
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('apply-form').classList.remove('hidden');
                }

                document.getElementById('line-login-button').addEventListener('click', function () {
                    liff.login({ redirectUri: window.location.href });
                });

                liff.init({ liffId: @json($liffId) })
                    .then(() => {
                        if (!liff.isLoggedIn()) {
                            document.getElementById('line-login-prompt').classList.remove('hidden');
                            tsnShowForm();
                            return null;
                        }
                        return Promise.all([liff.getProfile(), liff.getFriendship()]);
                    })
                    .then((results) => {
                        if (!results) return;
                        const [profile, friendship] = results;
                        document.getElementById('line_uid').value = profile.userId;
                        document.getElementById('line_display_name').value = profile.displayName;
                        document.getElementById('line_name_display').value = profile.displayName;
                        document.getElementById('is_friend').value = (friendship && friendship.friendFlag) ? '1' : '0';
                        tsnShowForm();
                    })
                    .catch((error) => {
                        console.error(error);
                        document.getElementById('line-login-prompt').classList.remove('hidden');
                        tsnShowForm();
                    });
            </script>
        @else
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const devUid = document.getElementById('dev_line_uid');
                    const devName = document.getElementById('dev_line_display_name');
                    const devFriend = document.getElementById('dev_is_friend');

                    document.getElementById('line_uid').value = devUid.value;
                    document.getElementById('line_display_name').value = devName.value;
                    document.getElementById('line_name_display').value = devName.value;
                    document.getElementById('is_friend').value = devFriend.checked ? '1' : '0';

                    document.getElementById('apply-form').addEventListener('submit', () => {
                        document.getElementById('line_uid').value = devUid.value;
                        document.getElementById('line_display_name').value = devName.value;
                        document.getElementById('is_friend').value = devFriend.checked ? '1' : '0';
                    });

                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('apply-form').classList.remove('hidden');
                });
            </script>
        @endif
    @endif
</body>
</html>
