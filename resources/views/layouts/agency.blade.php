<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'パートナーマイページ') - TSUNAGU</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900">
    @auth('agency')
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <span class="font-semibold">TSUNAGU パートナーマイページ</span>
                    <nav class="flex gap-4 text-sm">
                        <a href="{{ route('agency.home') }}" class="hover:text-blue-600 {{ request()->routeIs('agency.home') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">ホーム</a>
                        <a href="{{ route('agency.projects.index') }}" class="hover:text-blue-600 {{ request()->routeIs('agency.projects.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">案件一覧</a>
                        <a href="{{ route('agency.inquiries.index') }}" class="hover:text-blue-600 {{ request()->routeIs('agency.inquiries.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">問い合わせ</a>
                        <a href="{{ route('agency.contracts.index') }}" class="hover:text-blue-600 {{ request()->routeIs('agency.contracts.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">着金・支払い</a>
                        <a href="{{ route('agency.profile.edit') }}" class="hover:text-blue-600 {{ request()->routeIs('agency.profile.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">プロフィール</a>
                    </nav>
                </div>
                <form method="POST" action="{{ route('agency.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-800">ログアウト</button>
                </form>
            </div>
        </header>
        <main class="flex-1">
            <div class="max-w-6xl mx-auto px-4 py-8">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm" style="white-space: pre-line">
                        {{ session('status') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
    @else
        @yield('content')
    @endauth
</body>
</html>
