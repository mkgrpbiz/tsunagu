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
@endsection
