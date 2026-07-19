<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '管理画面') - TSUNAGU</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900">
    @auth
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-gray-200">
            <div class="px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <span class="font-semibold">TSUNAGU 管理画面</span>
                    <nav class="flex flex-wrap gap-4 text-sm">
                        @if (auth()->user()->canAccessMenu('dashboard'))
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.dashboard') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">ダッシュボード</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('projects'))
                            <a href="{{ route('admin.projects.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.projects.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">案件</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('categories'))
                            <a href="{{ route('admin.categories.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.categories.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">カテゴリー</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('agencies'))
                            <a href="{{ route('admin.agencies.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.agencies.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">パートナー</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('collaboration_partners'))
                            <a href="{{ route('admin.collaboration-partners.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.collaboration-partners.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">共創パートナー</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('inquiries'))
                            <a href="{{ route('admin.inquiries.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.inquiries.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">問い合わせ</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('deposit_links'))
                            <a href="{{ route('admin.deposit-links.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.deposit-links.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">着金紐付け</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('payments'))
                            <a href="{{ route('admin.payments.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.payments.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">支払い</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('announcements'))
                            <a href="{{ route('admin.announcements.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.announcements.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">お知らせ</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('collaboration_referrals'))
                            <a href="{{ route('admin.collaboration-referrals.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.collaboration-referrals.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">共創紹介</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('collaboration_rewards'))
                            <a href="{{ route('admin.collaboration-rewards.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.collaboration-rewards.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">共創報酬管理</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('legal_documents'))
                            <a href="{{ route('admin.legal-documents.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.legal-documents.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">契約管理</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('home'))
                            <a href="{{ route('admin.home-blocks.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.home-blocks.*') || request()->routeIs('admin.home-content.*') || request()->routeIs('admin.sales-materials.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">ホーム</a>
                        @endif
                        @if (auth()->user()->canAccessMenu('landing_page_content'))
                            <a href="{{ route('admin.landing-page-content.edit') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.landing-page-content.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">LP</a>
                        @endif
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.admins.index') }}" class="hover:text-blue-600 {{ request()->routeIs('admin.admins.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">管理者</a>
                        @endif
                    </nav>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.profile.edit') }}" class="text-sm hover:text-blue-600 {{ request()->routeIs('admin.profile.*') ? 'font-semibold text-blue-600' : 'text-gray-600' }}">プロフィール</a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-gray-800">ログアウト</button>
                    </form>
                </div>
            </div>
        </header>
        <main class="flex-1">
            <div class="px-6 py-8">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
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
