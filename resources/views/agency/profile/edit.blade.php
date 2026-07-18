@extends('layouts.agency')

@section('title', 'プロフィール')

@section('content')
<h1 class="text-xl font-semibold mb-6">プロフィール</h1>

@if ($agency->must_change_password)
    <div class="mb-6 rounded-md bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
        初期パスワードのままです。下部の「パスワード変更」からパスワードを変更してください。
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="bg-white border border-gray-200 rounded-lg p-6">
    <form method="POST" action="{{ route('agency.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前</label>
                <input type="text" name="name" id="name" value="{{ old('name', $agency->name) }}" required
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="name_kana" class="block text-sm font-medium text-gray-700 mb-1">フリガナ</label>
                <input type="text" name="name_kana" id="name_kana" value="{{ old('name_kana', $agency->name_kana) }}" required
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">性別</label>
                <select name="gender" id="gender" required
                        class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach ($genders as $gender)
                        <option value="{{ $gender->value }}" @selected(old('gender', $agency->gender->value) == $gender->value)>{{ $gender->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="prefecture" class="block text-sm font-medium text-gray-700 mb-1">お住まい（都道府県）</label>
                <input type="text" name="prefecture" id="prefecture" value="{{ old('prefecture', $agency->prefecture) }}" required
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">ご職業</label>
                <input type="text" name="occupation" id="occupation" value="{{ old('occupation', $agency->occupation) }}"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $agency->phone) }}" required
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="col-span-2">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
                <input type="email" name="email" id="email" value="{{ old('email', $agency->email) }}" required
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="col-span-2 border-t border-gray-100 pt-4 mt-2">
                <p class="text-sm font-medium text-gray-700 mb-3">活動情報</p>
            </div>

            <div class="col-span-2">
                <span class="block text-sm font-medium text-gray-700 mb-1">活動区分</span>
                <div class="flex flex-wrap gap-4">
                    @foreach ($activityTypes as $activityType)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="activity_type" value="{{ $activityType->value }}" required
                                   onchange="tsnUpdateCompanyNameField()"
                                   @checked(old('activity_type', $agency->activity_type?->value) === $activityType->value)>
                            {{ $activityType->label() }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="col-span-2 hidden" id="company_name_wrap">
                <label for="company_name" id="company_name_label" class="block text-sm font-medium text-gray-700 mb-1">屋号名</label>
                <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $agency->company_name) }}"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="col-span-2">
                <span class="block text-sm font-medium text-gray-700 mb-1">希望する活動内容（複数選択可）</span>
                <div class="flex flex-wrap gap-4">
                    @foreach ($desiredActivityOptions as $option)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="desired_activities[]" value="{{ $option }}"
                                   @checked(collect(old('desired_activities', $agency->desired_activities ?? []))->contains($option))>
                            {{ $option }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="col-span-2">
                <label for="current_activity" class="block text-sm font-medium text-gray-700 mb-1">現在の活動内容</label>
                <textarea name="current_activity" id="current_activity" rows="3" required
                          class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('current_activity', $agency->current_activity) }}</textarea>
            </div>

            <div class="col-span-2">
                <label for="media_urls" class="block text-sm font-medium text-gray-700 mb-1">媒体URL（任意）</label>
                <p class="text-xs text-gray-500 mb-1">複数ある場合は改行してください。</p>
                <textarea name="media_urls" id="media_urls" rows="3"
                          class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('media_urls', $agency->media_urls) }}</textarea>
            </div>

            <div class="col-span-2">
                <label for="track_record" class="block text-sm font-medium text-gray-700 mb-1">活動実績（任意）</label>
                <textarea name="track_record" id="track_record" rows="3"
                          class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('track_record', $agency->track_record) }}</textarea>
            </div>

            <div class="col-span-2">
                <label for="self_pr" class="block text-sm font-medium text-gray-700 mb-1">自己PR・TSUNAGUで取り組みたいこと（任意）</label>
                <textarea name="self_pr" id="self_pr" rows="3"
                          class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('self_pr', $agency->self_pr) }}</textarea>
            </div>

            <div class="col-span-2 border-t border-gray-100 pt-4 mt-2">
                <p class="text-sm font-medium text-gray-700 mb-3">振込先情報</p>
            </div>

            <div>
                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">銀行名</label>
                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $agency->bank_name) }}" autocomplete="off"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <input type="hidden" name="bank_code" id="bank_code" value="{{ old('bank_code', $agency->bank_code) }}">
            </div>

            <div>
                <label for="bank_branch_name" class="block text-sm font-medium text-gray-700 mb-1">支店名</label>
                <input type="text" name="bank_branch_name" id="bank_branch_name" value="{{ old('bank_branch_name', $agency->bank_branch_name) }}" autocomplete="off"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <input type="hidden" name="bank_branch_code" id="bank_branch_code" value="{{ old('bank_branch_code', $agency->bank_branch_code) }}">
            </div>

            <div>
                <label for="bank_account_type" class="block text-sm font-medium text-gray-700 mb-1">口座種別</label>
                <select name="bank_account_type" id="bank_account_type"
                        class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">選択してください</option>
                    @foreach ($bankAccountTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('bank_account_type', $agency->bank_account_type?->value) == $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">口座番号</label>
                <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $agency->bank_account_number) }}"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="col-span-2">
                <label for="bank_account_holder" class="block text-sm font-medium text-gray-700 mb-1">口座名義（カナ）</label>
                <input type="text" name="bank_account_holder" id="bank_account_holder" value="{{ old('bank_account_holder', $agency->bank_account_holder) }}"
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="col-span-2 border-t border-gray-100 pt-4 mt-2">
                <p class="text-sm font-medium text-gray-700 mb-3">パスワード変更（変更する場合のみ入力）</p>
            </div>

            <div class="col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1 whitespace-nowrap">現在のパスワード</label>
                    <input type="password" name="current_password" id="current_password"
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1 whitespace-nowrap">新しいパスワード</label>
                    <input type="password" name="password" id="password"
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1 whitespace-nowrap">新しいパスワード（確認）</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">保存</button>
        </div>
    </form>
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
</script>
@endsection
