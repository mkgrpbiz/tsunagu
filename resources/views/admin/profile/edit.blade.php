@extends('layouts.admin')

@section('title', 'プロフィール')

@section('content')
<h1 class="text-xl font-semibold mb-6">プロフィール</h1>

@if ($admin->must_change_password)
    <div class="mb-6 rounded-md bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
        初期パスワードのままです。下記からパスワードを変更してください。
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    <p class="text-sm text-gray-500 mb-4">{{ $admin->name }}（{{ $admin->email }}）</p>

    <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">現在のパスワード</label>
            <input type="password" name="current_password" id="current_password" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">新しいパスワード</label>
            <input type="password" name="password" id="password" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">新しいパスワード（確認）</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                   class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-4 py-2">
            パスワードを変更
        </button>
    </form>
</div>
@endsection
