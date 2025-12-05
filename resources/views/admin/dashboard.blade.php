@extends('layouts.admin')

@section('content')
    @php
        $proxyKeys = $proxyKeys ?? collect();
        $stats = $stats ?? [
            'total' => 0,
            'pending' => 0,
            'success' => 0,
            'failed' => 0,
            'proxies_running' => 0,
            'proxies_total' => 0,
            'latest_error' => null,
        ];
    @endphp

    <header class="flex flex-col gap-2">
        <p class="inline-flex items-center gap-2 text-sm text-[#1877f2] font-semibold">
            <span class="text-lg"></span>
            <a href="https://fb.com/treconyl" class="hover:underline text-[#1877f2]" target="_blank" rel="noopener">https://fb.com/treconyl</a>
        </p>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-3xl font-semibold text-slate-900">Tổng quan hệ thống</h2>
            <a href="{{ route('admin.accounts.export') }}" class="btn-sunrise-ghost text-sm px-6">Xuất CSV</a>
        </div>
        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm">{{ $errors->first() }}</div>
        @endif
    </header>

    <section class="grid gap-5 md:grid-cols-4">
        @php
            $statCards = [
                ['title' => 'Tổng tài khoản', 'value' => $stats['total'], 'meta' => $stats['pending'].' đang chờ xử lý'],
                ['title' => 'Thành công', 'value' => $stats['success'], 'meta' => number_format($stats['total'] ? ($stats['success']/max($stats['total'],1)*100) : 0, 1).'% hoàn tất'],
                ['title' => 'Thất bại', 'value' => $stats['failed'], 'meta' => 'Theo dõi lỗi gần nhất'],
                ['title' => 'Proxy chạy', 'value' => $stats['proxies_running'], 'meta' => 'Trong '.$stats['proxies_total'].' key'],
            ];
        @endphp
        @foreach($statCards as $card)
            <article class="sunrise-card p-6 border-2 border-[#ffd0aa] shadow-[0_14px_0_#f7c69b] rounded-[26px]">
                <h3 class="text-lg font-semibold text-slate-900">{{ $card['title'] }}</h3>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $card['value'] }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $card['meta'] }}</p>
                <div class="mt-4 flex items-center justify-between text-sm font-semibold text-[#f2783f]">
                    <span>Chi tiết</span>
                    <span>→</span>
                </div>
            </article>
        @endforeach
    </section>

    <div class="grid gap-6">
        <section id="proxy" class="sunrise-card p-6 space-y-6">
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-4 bg-white/70">
                    <h3 class="font-semibold text-slate-900">Import tài khoản</h3>
                    <form method="POST" action="{{ route('admin.accounts.import') }}" enctype="multipart/form-data" class="flex flex-col gap-3 mt-4 text-sm">
                        @csrf
                        <label class="text-slate-500">File CSV/TXT</label>
                        <input type="file" name="file" accept=".txt,.csv" class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm" required>
                        <button type="submit" class="btn-sunrise justify-center">Tải lên</button>
                        <p class="text-xs text-slate-400">Định dạng: <code>login|password</code> hoặc <code>login,password</code></p>
                    </form>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4 bg-white/70">
                    <h3 class="font-semibold text-slate-900">Thêm proxy key</h3>
                    <form method="POST" action="{{ route('admin.proxy.store') }}" class="flex flex-col gap-3 mt-4 text-sm">
                        @csrf
                        <div>
                            <label class="text-slate-500">Tên key</label>
                            <input type="text" name="label" class="w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Key FPT" required>
                        </div>
                        <div>
                            <label class="text-slate-500">API key</label>
                            <textarea name="api_key" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2" required></textarea>
                        </div>
                        <label class="inline-flex items-center gap-2 text-slate-600">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                            Kích hoạt ngay
                        </label>
                        <button type="submit" class="btn-sunrise justify-center">Lưu key</button>
                    </form>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-slate-900">Danh sách key</h3>
                    <p class="text-xs text-slate-500">{{ $proxyKeys->count() }} key</p>
                </div>
                <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Tên</th>
                            <th class="px-4 py-3 text-left">Trạng thái</th>
                            <th class="px-4 py-3 text-left">IP hiện tại</th>
                            <th class="px-4 py-3 text-left">Lần dùng gần nhất</th>
                            <th class="px-4 py-3 text-left">Hành động</th>
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
                                                <button type="submit" class="sunrise-chip sunrise-chip--ghost">Chạy</button>
                                            </form>
                                        @endif
                                        @if($proxy->status === 'running')
                                            <form method="POST" action="{{ route('admin.proxy.stop', $proxy) }}">
                                                @csrf
                                                <button type="submit" class="sunrise-chip sunrise-chip--rose">Dừng</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.proxy.test', $proxy) }}">
                                            @csrf
                                            <input type="hidden" name="nhamang" value="random">
                                            <input type="hidden" name="tinhthanh" value="0">
                                            <button type="submit" class="sunrise-chip sunrise-chip--ghost">Test API</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.proxy.rotate', $proxy) }}">
                                            @csrf
                                            <button type="submit" class="sunrise-chip">Xoay IP</button>
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
