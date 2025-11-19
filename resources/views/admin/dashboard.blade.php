@extends('layouts.admin')

@section('content')
    @php($proxyKeys = $proxyKeys ?? collect())
    @php($stats = $stats ?? [
        'total' => 0,
        'pending' => 0,
        'success' => 0,
        'failed' => 0,
        'proxies_running' => 0,
        'proxies_total' => 0,
        'latest_error' => null,
    ])

    <header class="flex flex-col gap-2">
        <p class="text-sm text-slate-500">Xin chÃ o</p>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-3xl font-semibold text-slate-900">Tá»•ng quan há»‡ thá»‘ng</h2>
            <a href="{{ route('admin.accounts.export') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-white">Xuáº¥t CSV</a>
        </div>
        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm">{{ $errors->first() }}</div>
        @endif
    </header>

    <section class="grid gap-4 md:grid-cols-4">
        <article class="rounded-2xl bg-white shadow-sm border border-white p-5">
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Tá»•ng tÃ i khoáº£n</p>
            <div class="text-3xl font-semibold text-slate-900">{{ $stats['total'] }}</div>
            <p class="text-xs text-slate-500 mt-1">{{ $stats['pending'] }} Ä‘ang chá» xá»­ lÃ½</p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-white p-5">
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">ThÃ nh cÃ´ng</p>
            <div class="text-3xl font-semibold text-emerald-600">{{ $stats['success'] }}</div>
            <p class="text-xs text-slate-500 mt-1">{{ number_format($stats['total'] ? ($stats['success']/max($stats['total'],1)*100) : 0, 1) }}% hoÃ n táº¥t</p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-white p-5">
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Tháº¥t báº¡i</p>
            <div class="text-3xl font-semibold text-rose-600">{{ $stats['failed'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Theo dÃµi lá»—i gáº§n nháº¥t</p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-white p-5">
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Proxy cháº¡y</p>
            <div class="text-3xl font-semibold text-slate-900">{{ $stats['proxies_running'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Trong {{ $stats['proxies_total'] }} key</p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-white p-5 md:col-span-2">
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Lá»—i gáº§n nháº¥t</p>
            <div class="text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($stats['latest_error'] ?? 'ChÆ°a ghi nháº­n lá»—i.', 160) }}</div>
        </article>
    </section>

    <div class="grid gap-6">
        <section id="proxy" class="rounded-2xl bg-white shadow-sm border border-white p-6 space-y-6">
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-900">Import tÃ i khoáº£n</h3>
                    <form method="POST" action="{{ route('admin.accounts.import') }}" enctype="multipart/form-data" class="flex flex-col gap-3 mt-4 text-sm">
                        @csrf
                        <label class="text-slate-500">File CSV/TXT</label>
                        <input type="file" name="file" accept=".txt,.csv" class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm" required>
                        <button type="submit" class="rounded-xl bg-slate-900 text-white py-2 font-medium">Táº£i lÃªn</button>
                        <p class="text-xs text-slate-400">Äá»‹nh dáº¡ng: <code>login|password</code> hoáº·c <code>login,password</code></p>
                    </form>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-900">ThÃªm proxy key</h3>
                    <form method="POST" action="{{ route('admin.proxy.store') }}" class="flex flex-col gap-3 mt-4 text-sm">
                        @csrf
                        <div>
                            <label class="text-slate-500">TÃªn key</label>
                            <input type="text" name="label" class="w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Key FPT" required>
                        </div>
                        <div>
                            <label class="text-slate-500">API key</label>
                            <textarea name="api_key" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2" required></textarea>
                        </div>
                        <label class="inline-flex items-center gap-2 text-slate-600">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                            KÃ­ch hoáº¡t ngay
                        </label>
                        <button type="submit" class="rounded-xl bg-slate-900 text-white py-2 font-medium">LÆ°u key</button>
                    </form>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-slate-900">Danh sÃ¡ch key</h3>
                    <p class="text-xs text-slate-500">{{ $proxyKeys->count() }} key</p>
                </div>
                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">TÃªn</th>
                            <th class="px-4 py-3 text-left">Tráº¡ng thÃ¡i</th>
                            <th class="px-4 py-3 text-left">IP hiá»‡n táº¡i</th>
                            <th class="px-4 py-3 text-left">Láº§n dÃ¹ng gáº§n nháº¥t</th>
                            <th class="px-4 py-3 text-left">HÃ nh Ä‘á»™ng</th>
                        </tr>
                        </thead>
                                                                        <tbody>
                        @forelse($proxyKeys as $proxy)
                            @php($meta = $proxy->meta ?? [])
                            @php($currentHttp = $meta['last_proxy_http'] ?? null)
                            @php($proxyUser = $meta['last_proxy_username'] ?? null)
                            @php($proxyPass = $meta['last_proxy_password'] ?? null)
                            @php($rotatedAt = isset($meta['last_proxy_rotated_at']) ? \Illuminate\Support\Carbon::parse($meta['last_proxy_rotated_at']) : null)
                            @php($isExpired = $rotatedAt ? $rotatedAt->lt(now()->subMinute()) : true)
                            @php($isRunning = $proxy->status === 'running' && ! $isExpired)
                            @php($statusClass = $isRunning ? 'bg-emerald-100 text-emerald-900' : 'bg-rose-100 text-rose-700')
                            @php($statusLabel = $isRunning ? 'Đang chạy' : 'Hết hạn')
                            @php($ipDisplay = $currentHttp ? $currentHttp . (($proxyUser && $proxyPass) ? ':' . $proxyUser . ':' . $proxyPass : '') : null)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">{{ $proxy->label }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    @if($ipDisplay)
                                        <div class="font-mono">{{ $ipDisplay }}</div>
                                        <div class="text-[11px] text-slate-400">Cập nhật: {{ $rotatedAt?->format('d/m/Y H:i:s') ?? 'Chưa có' }}</div>
                                    @else
                                        <span class="text-slate-400">Chưa có IP</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ optional($proxy->last_used_at)->format('d/m/Y H:i') ?? 'Chưa sử dụng' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if($proxy->status !== 'running')
                                            <form method="POST" action="{{ route('admin.proxy.start', $proxy) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded-full bg-slate-900 text-white text-xs cursor-pointer">Chạy</button>
                                            </form>
                                        @endif
                                        @if($proxy->status === 'running')
                                            <form method="POST" action="{{ route('admin.proxy.stop', $proxy) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-xs cursor-pointer">Dừng</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.proxy.test', $proxy) }}">
                                            @csrf
                                            <input type="hidden" name="nhamang" value="random">
                                            <input type="hidden" name="tinhthanh" value="0">
                                            <button type="submit" class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs cursor-pointer">Test API</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.proxy.rotate', $proxy) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs cursor-pointer">Xoay IP</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400">Chưa có key nào.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection



