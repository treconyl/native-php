<!DOCTYPE html>
<html lang="vi" class="bg-[#fff7f2]">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Password Ops' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.24.2/dist/css/uikit.min.css" />
</head>
@php($activeNav = $activeNav ?? 'dashboard')

<body class="bg-[#fff7f2] text-slate-800 min-h-screen antialiased">
    <div class="min-h-screen flex sunrise-shell">
        <aside class="hidden lg:flex lg:flex-col w-80 shrink-0 px-7 py-8 gap-8 lg:sticky top-0 h-screen">
            <div class="sunrise-card p-5 space-y-6">
                <div class="sunrise-badge">
                    <span>#Password Ops Hub</span>
                </div>
                <div>
                    <h1 class="text-3xl! font-semibold text-slate-900 leading-none">Native Password</h1>
                    <p class="text-sm text-slate-500">Automation & Proxy Control</p>
                </div>
            </div>
            <nav class="flex flex-col gap-4 text-sm font-medium">
                @php($navItems = [
                    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'key' => 'dashboard', 'hint' => 'Tổng quan'],
                    ['label' => 'Proxy Keys', 'route' => 'admin.proxies.index', 'key' => 'proxies', 'hint' => 'IP xoay'],
                    ['label' => 'Accounts', 'route' => 'admin.accounts.list', 'key' => 'accounts', 'hint' => 'Danh sách'],
                    ['label' => 'Account Test', 'route' => 'admin.garena.index', 'key' => 'garena', 'hint' => 'Playwright'],
                ])
                @foreach ($navItems as $item)
                @php($isActive = $activeNav === $item['key'])
                <a href="{{ route($item['route']) }}" data-active="{{ $isActive ? 'true' : 'false' }}"
                    class="sunrise-nav hover:no-underline!">
                    <span class="sunrise-nav__dot"></span>
                    <div class="flex flex-col leading-tight">
                        <span class="text-base">{{ $item['label'] }}</span>
                        <span class="text-[11px] text-slate-500/80">{{ $item['hint'] }}</span>
                    </div>
                    @if ($isActive)
                        <span class="ml-auto text-[11px] uppercase tracking-widest text-white/80">Live</span>
                    @endif
                </a>
                @endforeach
            </nav>
            <div class="mt-auto space-y-3 text-xs text-slate-600">
                <div class="sunrise-card px-5 py-4">
                    <p class="font-semibold text-slate-800 text-sm">Proxy xoay mỗi 1 phút</p>
                    <p class="text-[11px] leading-relaxed mt-1">Hệ thống chỉ chạy khi có key proxy.vn. Nếu key hết hạn
                        trước 1 phút, chờ hết chu kỳ xoay.</p>
                </div>
            </div>
        </aside>

        <main class="flex-1 px-4 py-6 sm:px-8 space-y-8">
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/uikit@3.24.2/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.24.2/dist/js/uikit-icons.min.js"></script>
    @stack('scripts')
</body>

</html>