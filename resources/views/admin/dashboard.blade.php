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
    @php($proxyStatusPalette = [
        'running' => 'bg-emerald-100 text-emerald-700',
        'stopping' => 'bg-amber-100 text-amber-700',
        'idle' => 'bg-slate-100 text-slate-600',
        'default' => 'bg-slate-200 text-slate-600',
    ])

    <header class="flex flex-col gap-2">
        <p class="text-sm text-slate-500">Xin chào, {{ auth()->user()->name ?? auth()->user()->email }}</p>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-3xl font-semibold text-slate-900">Tổng quan hệ thống</h2>
            <a href="{{ route('admin.accounts.export') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-white">Xuất CSV</a>
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
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Tổng tài khoản</p>
            <div class="text-3xl font-semibold text-slate-900">{{ $stats['total'] }}</div>
            <p class="text-xs text-slate-500 mt-1">{{ $stats['pending'] }} đang chờ xử lý</p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-white p-5">
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Thành công</p>
            <div class="text-3xl font-semibold text-emerald-600">{{ $stats['success'] }}</div>
            <p class="text-xs text-slate-500 mt-1">{{ number_format($stats['total'] ? ($stats['success']/max($stats['total'],1)*100) : 0, 1) }}% hoàn tất</p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-white p-5">
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Thất bại</p>
            <div class="text-3xl font-semibold text-rose-600">{{ $stats['failed'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Theo dõi lỗi gần nhất</p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-white p-5">
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Proxy chạy</p>
            <div class="text-3xl font-semibold text-slate-900">{{ $stats['proxies_running'] }}</div>
            <p class="text-xs text-slate-500 mt-1">Trong {{ $stats['proxies_total'] }} key</p>
        </article>
        <article class="rounded-2xl bg-white shadow-sm border border-white p-5 md:col-span-2">
            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Lỗi gần nhất</p>
            <div class="text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($stats['latest_error'] ?? 'Chưa ghi nhận lỗi.', 160) }}</div>
        </article>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <section id="proxy" class="rounded-2xl bg-white shadow-sm border border-white p-6 xl:col-span-2 space-y-6">
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-900">Import tài khoản</h3>
                    <form method="POST" action="{{ route('admin.accounts.import') }}" enctype="multipart/form-data" class="flex flex-col gap-3 mt-4 text-sm">
                        @csrf
                        <label class="text-slate-500">File CSV/TXT</label>
                        <input type="file" name="file" accept=".txt,.csv" class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm" required>
                        <button type="submit" class="rounded-xl bg-slate-900 text-white py-2 font-medium">Tải lên</button>
                        <p class="text-xs text-slate-400">Định dạng: <code>login|password</code> hoặc <code>login,password</code></p>
                    </form>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
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
                        <button type="submit" class="rounded-xl bg-slate-900 text-white py-2 font-medium">Lưu key</button>
                    </form>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-slate-900">Danh sách key</h3>
                    <p class="text-xs text-slate-500">{{ $proxyKeys->count() }} key</p>
                </div>
                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Tên</th>
                            <th class="px-4 py-3 text-left">Trạng thái</th>
                            <th class="px-4 py-3 text-left">Lần dùng gần nhất</th>
                            <th class="px-4 py-3 text-left">Hành động</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($proxyKeys as $proxy)
                            @php($palette = $proxyStatusPalette[$proxy->status] ?? $proxyStatusPalette['default'])
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">{{ $proxy->label }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium {{ $palette }}">
                                        {{ ucfirst($proxy->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ optional($proxy->last_used_at)->format('d/m/Y H:i') ?? 'Chưa sử dụng' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if($proxy->status !== 'running')
                                            <form method="POST" action="{{ route('admin.proxy.start', $proxy) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded-full bg-slate-900 text-white text-xs">Chạy</button>
                                            </form>
                                        @endif
                                        @if($proxy->status === 'running' || $proxy->status === 'stopping')
                                            <form method="POST" action="{{ route('admin.proxy.stop', $proxy) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-xs">Dừng</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.proxy.test', $proxy) }}">
                                            @csrf
                                            <input type="hidden" name="nhamang" value="random">
                                            <input type="hidden" name="tinhthanh" value="0">
                                            <button type="submit" class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs">Test API</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400">Chưa có key nào.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="accounts" class="rounded-2xl bg-white shadow-sm border border-white p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-900">Danh sách tài khoản ({{ $accounts->total() ?? 0 }})</h3>
            </div>
            <div class="rounded-2xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                    <tr>
                        <th class="px-3 py-3 text-left">Login</th>
                        <th class="px-3 py-3 text-left">Mật khẩu hiện tại</th>
                        <th class="px-3 py-3 text-left">Mật khẩu mới</th>
                        <th class="px-3 py-3 text-left">Trạng thái</th>
                        <th class="px-3 py-3 text-left">Lần thử cuối</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($accounts as $account)
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-3 font-medium text-slate-900">{{ $account->login }}</td>
                            <td class="px-3 py-3">{{ $account->current_password ? \Illuminate\Support\Str::limit($account->current_password, 30) : 'Chưa nhập' }}</td>
                            <td class="px-3 py-3">{{ $account->next_password ? \Illuminate\Support\Str::limit($account->next_password, 30) : 'Chưa đổi' }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-slate-100 text-slate-600">{{ $account->status }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-500">{{ optional($account->last_attempted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-slate-400">Chưa có tài khoản nào.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $accounts->links() }}
            </div>
        </section>
    </div>
@endsection
