@extends('layouts.public')

@section('title', 'パートナー 新規登録')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-2xl bg-white border border-gray-200 rounded-lg p-8">
        <h1 class="text-lg font-semibold mb-4 text-center">TSUNAGU パートナー 新規登録</h1>

        <div class="bg-blue-50 border border-blue-100 rounded-md px-4 py-3 text-sm text-gray-700 mb-6 leading-relaxed">
            TSUNAGUは、審査制の共創型ビジネスプラットフォームです。<br><br>
            ご登録内容をもとに運営による審査を行い、承認後に案件一覧および各種機能をご利用いただけます。<br><br>
            活動実績の有無だけで判断することはありませんので、現在の活動内容や今後取り組みたいことなどをご記入ください。
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('agency.register.store') }}" class="space-y-6">
            @csrf

            <div>
                <h2 class="text-sm font-semibold text-gray-700 mb-3">基本情報</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="name_kana" class="block text-sm font-medium text-gray-700 mb-1">フリガナ</label>
                        <input type="text" name="name_kana" id="name_kana" value="{{ old('name_kana') }}" required
                               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">性別</label>
                        <select name="gender" id="gender" required
                                class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">選択してください</option>
                            @foreach ($genders as $gender)
                                <option value="{{ $gender->value }}" @selected(old('gender') == $gender->value)>{{ $gender->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="prefecture" class="block text-sm font-medium text-gray-700 mb-1">お住まい（都道府県）</label>
                        <input type="text" name="prefecture" id="prefecture" value="{{ old('prefecture') }}" required
                               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">ご職業</label>
                        <input type="text" name="occupation" id="occupation" value="{{ old('occupation') }}" required
                               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required
                               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1 whitespace-nowrap">パスワード</label>
                        <input type="password" name="password" id="password" required minlength="8"
                               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1 whitespace-nowrap">パスワード（確認）</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="referral_code" class="block text-sm font-medium text-gray-700 mb-1">紹介者パートナーコード{{ $referralCode ? '' : '（任意）' }}</label>
                    @if ($referralCode)
                        <input type="text" name="referral_code" id="referral_code" value="{{ $referralCode }}" readonly
                               class="w-full rounded-md border border-gray-300 bg-gray-50 text-gray-600 shadow-sm">
                        <p class="text-xs text-gray-500 mt-1">紹介リンクから設定されました。</p>
                    @else
                        <input type="text" name="referral_code" id="referral_code" value="{{ old('referral_code') }}"
                               placeholder="例：B0001"
                               class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @endif
                </div>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">活動情報</h2>

                <div class="mb-4">
                    <span class="block text-sm font-medium text-gray-700 mb-1">活動区分</span>
                    <div class="flex flex-wrap gap-4">
                        @foreach ($activityTypes as $activityType)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="radio" name="activity_type" value="{{ $activityType->value }}" required
                                       onchange="tsnUpdateCompanyNameField()"
                                       @checked(old('activity_type') === $activityType->value)>
                                {{ $activityType->label() }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-4 hidden" id="company_name_wrap">
                    <label for="company_name" id="company_name_label" class="block text-sm font-medium text-gray-700 mb-1">屋号名</label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <span class="block text-sm font-medium text-gray-700 mb-1">希望する活動内容（複数選択可）</span>
                    <div class="flex flex-wrap gap-4">
                        @foreach ($desiredActivityOptions as $option)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="desired_activities[]" value="{{ $option }}"
                                       @checked(collect(old('desired_activities', []))->contains($option))>
                                {{ $option }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-4">
                    <label for="current_activity" class="block text-sm font-medium text-gray-700 mb-1">現在の活動内容</label>
                    <p class="text-xs text-gray-500 mb-1">現在行っている事業・営業活動・紹介活動などをご記入ください。</p>
                    <textarea name="current_activity" id="current_activity" rows="3" required
                              class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('current_activity') }}</textarea>
                </div>

                <div class="mb-4">
                    <label for="media_urls" class="block text-sm font-medium text-gray-700 mb-1">媒体URL（任意）</label>
                    <p class="text-xs text-gray-500 mb-1">ホームページ、SNS、ブログ、LINE公式アカウントなどをご記入ください。複数ある場合は改行してください。</p>
                    <textarea name="media_urls" id="media_urls" rows="3"
                              class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('media_urls') }}</textarea>
                </div>

                <div class="mb-4">
                    <label for="track_record" class="block text-sm font-medium text-gray-700 mb-1">活動実績（任意）</label>
                    <p class="text-xs text-gray-500 mb-1">営業実績、紹介実績、運営媒体、得意分野などがあればご記入ください。実績がない場合は「なし」とご記入ください。</p>
                    <textarea name="track_record" id="track_record" rows="3"
                              class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('track_record') }}</textarea>
                </div>

                <div>
                    <label for="self_pr" class="block text-sm font-medium text-gray-700 mb-1">自己PR・TSUNAGUで取り組みたいこと（任意）</label>
                    <p class="text-xs text-gray-500 mb-1">TSUNAGUを利用する目的や、今後取り組みたい案件・共創内容などをご自由にご記入ください。</p>
                    <textarea name="self_pr" id="self_pr" rows="3"
                              class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('self_pr') }}</textarea>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-6 space-y-2">
                <p class="text-xs text-gray-500 mb-2">各文書名をクリックして内容をご確認いただくと、同意のチェックができるようになります。</p>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="terms_agreed" value="1" required disabled data-agree-checkbox="terms" class="mt-0.5">
                    <span><button type="button" class="text-blue-600 hover:underline" data-legal-open="terms">利用規約</button>に同意します</span>
                </label>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="privacy_agreed" value="1" required disabled data-agree-checkbox="privacy" class="mt-0.5">
                    <span><button type="button" class="text-blue-600 hover:underline" data-legal-open="privacy">プライバシーポリシー</button>に同意します</span>
                </label>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="partner_agreement_agreed" value="1" required disabled data-agree-checkbox="partner_agreement" class="mt-0.5">
                    <span><button type="button" class="text-blue-600 hover:underline" data-legal-open="partner_agreement">パートナー業務委託契約書</button>に同意します</span>
                </label>
            </div>

            @foreach ($legalDocuments as $type => $document)
                <div class="hidden" id="legal-content-{{ $type }}" data-title="{{ $document?->title }}">{{ $document?->body }}</div>
            @endforeach

            <div id="legal-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
                <div id="legal-modal-panel" class="bg-white rounded-lg max-w-lg w-full max-h-[80vh] overflow-y-auto p-6">
                    <h3 id="legal-modal-title" class="font-semibold text-lg mb-4"></h3>
                    <div id="legal-modal-body" class="text-sm text-gray-700 leading-relaxed" style="white-space: pre-line"></div>
                    <button type="button" id="legal-modal-close" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md py-2">閉じる</button>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md py-2">
                パートナー登録を申請する
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            すでに登録済みの方は
            <a href="{{ route('agency.login') }}" class="text-blue-600 hover:underline">ログインはこちら</a>
        </p>
    </div>
</div>

<script>
function tsnUpdateCompanyNameField() {
    var checked = document.querySelector('input[name="activity_type"]:checked');
    var wrap = document.getElementById('company_name_wrap');
    var label = document.getElementById('company_name_label');
    var input = document.getElementById('company_name');
    var value = checked ? checked.value : null;

    if (value === 'sole_proprietor') {
        wrap.classList.remove('hidden');
        label.textContent = '屋号名（任意）';
        input.required = false;
    } else if (value === 'corporation') {
        wrap.classList.remove('hidden');
        label.textContent = '法人名';
        input.required = true;
    } else {
        wrap.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}

document.addEventListener('DOMContentLoaded', tsnUpdateCompanyNameField);

(function () {
    var modal = document.getElementById('legal-modal');
    var modalPanel = document.getElementById('legal-modal-panel');
    var modalTitle = document.getElementById('legal-modal-title');
    var modalBody = document.getElementById('legal-modal-body');

    document.querySelectorAll('[data-legal-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            var type = button.dataset.legalOpen;
            var source = document.getElementById('legal-content-' + type);

            modalTitle.textContent = source.dataset.title;
            modalBody.textContent = source.textContent;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modalPanel.scrollTop = 0;

            var checkbox = document.querySelector('[data-agree-checkbox="' + type + '"]');
            checkbox.disabled = false;
        });
    });

    document.getElementById('legal-modal-close').addEventListener('click', function () {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
})();
</script>
@endsection
