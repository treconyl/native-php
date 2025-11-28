<!DOCTYPE html>
<html lang="vi" class="bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Password Ops' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.24.2/dist/css/uikit.min.css" />
</head>
@php($activeNav = $activeNav ?? 'dashboard')
<body class="bg-slate-100 text-slate-800 min-h-screen antialiased">
    <div class="min-h-screen flex">
        <aside class="hidden lg:flex lg:flex-col w-72 shrink-0 bg-white border-r border-slate-200 px-6 py-8 gap-8 lg:sticky top-0 h-screen">
            <div class="space-y-3">
                <p class="text-[11px] uppercase tracking-[0.35em] text-slate-400">Automation</p>
                <div>
                    <h1 class="text-2xl! font-semibold text-slate-900">Password Ops</h1>
                </div>
            </div>
            <nav class="flex flex-col gap-2 text-sm font-medium">
                @php($navItems = [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'key' => 'dashboard'],
                ['label' => 'Proxy Keys', 'route' => 'admin.proxies.index', 'key' => 'proxies'],
                ['label' => 'Accounts', 'route' => 'admin.accounts.list', 'key' => 'accounts'],
                ['label' => 'Garena Test', 'route' => 'admin.garena.index', 'key' => 'garena'],
                ])
                @foreach ($navItems as $item)
                @php($isActive = $activeNav === $item['key'])
                <a href="{{ route($item['route']) }}" class="group flex items-center gap-3 px-4 py-3 rounded-2xl transition hover:no-underline! {{ $isActive ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 border border-transparent hover:border-slate-200' }}">
                    <span class="h-2 w-2 rounded-full {{ $isActive ? 'bg-emerald-300' : 'bg-slate-300 group-hover:bg-emerald-200' }}"></span>
                    <span>{{ $item['label'] }}</span>
                    @if ($isActive)
                    <span class="ml-auto text-[10px] uppercase tracking-widest text-emerald-200">Now</span>
                    @endif
                </a>
                @endforeach
            </nav>
            <div class="mt-auto space-y-3 text-xs text-slate-500">
                <div class="rounded-2xl border border-slate-200 px-4 py-3 bg-slate-50 text-slate-600">
                    <p class="font-semibold text-slate-800 text-sm">Proxy xoay mỗi 1 phút</p>
                    <p class="text-[11px] leading-relaxed mt-1">Hệ thống chỉ chạy khi có key của proxy.vn. Nếu key hết hạn trước khi đủ 1 phút thì vẫn phải chờ cho hết chu kỳ xoay mới được tiếp tục.</p>
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
