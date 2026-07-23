@extends('layouts.agency')

@section('title', 'ログイン')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white border border-gray-200 rounded-lg p-8">
        <h1 class="text-lg font-semibold mb-6 text-center">TSUNAGU パートナーマイページ</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('agency.login.attempt') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
                <input type="password" name="password" id="password" required
                       class="w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border border-gray-300">
                ログイン状態を保持する
            </label>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md py-2">
                ログイン
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            パートナーとして登録がまだの方は<br>
            <a href="{{ route('agency.register') }}" class="text-blue-600 hover:underline">新規登録はこちら</a>
        </p>
    </div>
</div>
@endsection
