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
    @php
        $authAdmin = auth()->user();
        $brandLogoPath = \App\Models\HomePageContent::current()->brand_logo_path;

        $navGroups = [
            '案件管理' => [
                'projects' => ['label' => '案件一覧', 'route' => 'admin.projects.index', 'active' => 'admin.projects.*'],
                'categories' => ['label' => 'カテゴリー', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*'],
            ],
            'パートナー管理' => [
                'agencies' => ['label' => 'パートナー一覧', 'route' => 'admin.agencies.index', 'active' => 'admin.agencies.*'],
                'collaboration_partners' => ['label' => '共創パートナー一覧', 'route' => 'admin.collaboration-partners.index', 'active' => 'admin.collaboration-partners.*'],
            ],
            '問い合わせ管理' => [
                'inquiries' => ['label' => '問い合わせ一覧', 'route' => 'admin.inquiries.index', 'active' => 'admin.inquiries.*'],
                'collaboration_referrals' => ['label' => '共創パートナー紹介', 'route' => 'admin.collaboration-referrals.index', 'active' => 'admin.collaboration-referrals.*'],
                'collaboration_partner_applications' => ['label' => '共創パートナー申請', 'route' => 'admin.collaboration-partner-applications.index', 'active' => 'admin.collaboration-partner-applications.*'],
            ],
            '報酬・支払管理' => [
                'deposit_links' => ['label' => '着金紐付け', 'route' => 'admin.deposit-links.index', 'active' => 'admin.deposit-links.*'],
                'payments' => ['label' => '支払い', 'route' => 'admin.payments.index', 'active' => 'admin.payments.*'],
                'collaboration_rewards' => ['label' => '共創報酬管理', 'route' => 'admin.collaboration-rewards.index', 'active' => 'admin.collaboration-rewards.*'],
            ],
            '各ページ管理' => [
                'home' => ['label' => 'ホーム編集', 'route' => 'admin.home-blocks.index', 'active' => ['admin.home-blocks.*', 'admin.home-content.*', 'admin.sales-materials.*']],
                'landing_page_content' => ['label' => 'LP編集', 'route' => 'admin.landing-page-content.edit', 'active' => 'admin.landing-page-content.*'],
                'legal_documents' => ['label' => '契約管理', 'route' => 'admin.legal-documents.index', 'active' => 'admin.legal-documents.*'],
                'announcements' => ['label' => 'お知らせ', 'route' => 'admin.announcements.index', 'active' => 'admin.announcements.*'],
            ],
        ];
    @endphp

    <div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/40 z-20 lg:hidden"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-30 w-64 bg-blue-700 text-blue-100 overflow-y-auto
                  flex flex-col transform -translate-x-full transition-transform duration-200">
        <div class="px-4 py-4 border-b border-blue-600 flex items-center shrink-0">
            @if ($brandLogoPath)
                <div class="bg-white rounded-xl shadow-sm px-3 py-2">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($brandLogoPath) }}" alt="TSUNAGU" class="h-14 w-auto">
                </div>
            @else
                <span class="font-bold text-lg text-white">TSUNAGU 管理画面</span>
            @endif
        </div>

        <nav class="py-2 text-sm flex-1">
            @if ($authAdmin->canAccessMenu('dashboard'))
                <a href="{{ route('admin.dashboard') }}"
                   class="block px-4 py-2.5 hover:bg-blue-600 transition-colors font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-blue-800' : '' }}">
                    ダッシュボード
                </a>
            @endif

            @foreach ($navGroups as $groupLabel => $items)
                @php
                    $visibleItems = collect($items)->filter(fn ($m, $key) => $authAdmin->canAccessMenu($key));
                    $groupIsActive = $visibleItems->contains(fn ($m) => request()->routeIs($m['active']));
                @endphp
                @if ($visibleItems->isNotEmpty())
                    <div class="border-t border-blue-600">
                        <button type="button" class="nav-group-header w-full flex items-center justify-between px-4 py-2.5 text-left hover:bg-blue-600 transition-colors font-medium">
                            <span>{{ $groupLabel }}</span>
                            <span class="nav-group-icon text-blue-200 text-xs select-none">{{ $groupIsActive ? '▼' : '▶' }}</span>
                        </button>
                        <div class="nav-group-body {{ $groupIsActive ? '' : 'hidden' }} bg-blue-800/40">
                            @foreach ($visibleItems as $key => $menu)
                                <a href="{{ route($menu['route']) }}"
                                   class="block pl-7 pr-4 py-2 hover:bg-blue-600 transition-colors {{ request()->routeIs($menu['active']) ? 'bg-blue-800 font-semibold' : '' }}">
                                    {{ $menu['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @if ($authAdmin->isAdmin())
                <div class="border-t border-blue-600">
                    <a href="{{ route('admin.admins.index') }}"
                       class="block px-4 py-2.5 hover:bg-blue-600 transition-colors font-medium {{ request()->routeIs('admin.admins.*') ? 'bg-blue-800' : '' }}">
                        管理者
                    </a>
                </div>
            @endif
        </nav>

        <div class="border-t border-blue-600 shrink-0">
            <a href="{{ route('admin.profile.edit') }}"
               class="block px-4 py-2.5 hover:bg-blue-600 transition-colors {{ request()->routeIs('admin.profile.*') ? 'bg-blue-800' : '' }}">
                プロフィール
            </a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"
                        class="block w-full text-left px-4 py-2.5 hover:bg-blue-600 transition-colors">
                    ログアウト
                </button>
            </form>
        </div>
    </aside>

    <div id="content-wrapper" class="transition-[padding-left] duration-200">
        <div class="bg-white border-b border-gray-200 shadow-sm flex items-center gap-3 px-4 py-3">
            <button id="sidebar-toggle" type="button" class="text-gray-600 p-1 -ml-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <main class="px-6 py-8">
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
        </main>
    </div>

    <script>
    (function () {
        var sidebar  = document.getElementById('sidebar');
        var overlay  = document.getElementById('sidebar-overlay');
        var toggle   = document.getElementById('sidebar-toggle');
        var wrapper  = document.getElementById('content-wrapper');

        function isDesktop() {
            return window.matchMedia('(min-width: 1024px)').matches;
        }

        function setSidebarOpen(open) {
            sidebar.classList.toggle('-translate-x-full', !open);
            wrapper.classList.toggle('sidebar-open', open);
            overlay.classList.toggle('hidden', !(open && !isDesktop()));
        }

        toggle.addEventListener('click', function () {
            setSidebarOpen(sidebar.classList.contains('-translate-x-full'));
        });
        overlay.addEventListener('click', function () { setSidebarOpen(false); });

        setSidebarOpen(isDesktop());

        document.querySelectorAll('.nav-group-header').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var body = btn.parentElement.querySelector('.nav-group-body');
                var icon = btn.querySelector('.nav-group-icon');
                var isOpen = !body.classList.contains('hidden');
                body.classList.toggle('hidden', isOpen);
                icon.textContent = isOpen ? '▶' : '▼';
            });
        });
    })();
    </script>
    @else
        @yield('content')
    @endauth
</body>
</html>
