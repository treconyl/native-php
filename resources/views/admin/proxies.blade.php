@extends('layouts.admin', ['activeNav' => 'proxies', 'title' => 'Proxy Keys'])

@section('content')
    <header class="flex flex-col gap-2">
        <h2 class="text-3xl font-semibold text-slate-900">Proxy Keys</h2>
        <p class="text-sm text-slate-500">Quản lý key xoay IP và trạng thái worker.</p>
        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm">{{ $errors->first() }}</div>
        @endif
    </header>

    <div class="rounded-2xl bg-white shadow-sm border border-white p-5 space-y-5">
        <div class="grid gap-3 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
                <h3 class="font-semibold text-slate-900">Thêm proxy key</h3>
                <form method="POST" action="{{ route('admin.proxy.store') }}" class="flex flex-col gap-2 text-sm">
                    @csrf
                    <div>
                        <label class="text-slate-500">Tên key</label>
                        <input type="text" name="label" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Key proxy.vn" required>
                    </div>
                    <div>
                        <label class="text-slate-500">API key</label>
                        <textarea name="api_key" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="xxxx-xxxx-xxxx" required></textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 text-slate-600 text-sm">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 h-4 w-4">
                        Kích hoạt ngay
                    </label>
                    <button type="submit" class="rounded-xl bg-slate-900 text-white py-2 font-semibold cursor-pointer text-sm">Lưu key</button>
                </form>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4 space-y-2">
                <h3 class="font-semibold text-slate-900">Thống kê</h3>
                <p class="text-sm text-slate-500">Tổng {{ $proxyKeys->count() }} key, {{ $proxyKeys->where('status','running')->count() }} đang chạy.</p>
                <p class="text-xs text-slate-400">Nhớ xoay IP sau mỗi phút để tránh bị chặn.</p>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[11px]">
                <tr>
                    <th class="px-3 py-2 text-left">Tên</th>
                    <th class="px-3 py-2 text-left">API Key</th>
                    <th class="px-3 py-2 text-left">Trạng thái</th>
                    <th class="px-3 py-2 text-left">IP hiện tại</th>
                    <th class="px-3 py-2 text-left">Lần dùng gần nhất</th>
                    <th class="px-3 py-2 text-left">Hành động</th>
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
                        <td class="px-3 py-2 font-medium text-slate-900">{{ $proxy->label }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ \Illuminate\Support\Str::limit($proxy->api_key, 36) }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-600">
                            @if($ipDisplay)
                                <div class="font-mono">{{ $ipDisplay }}</div>
                                <div class="text-[11px] text-slate-400">Cập nhật: {{ $rotatedAt?->format('d/m/Y H:i:s') ?? 'Chưa có' }}</div>
                            @else
                                <span class="text-slate-400">Chưa có IP</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-sm text-slate-600">{{ optional($proxy->last_used_at)->format('d/m/Y H:i') ?? 'Chưa sử dụng' }}</td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap gap-1.5">
                                @if($proxy->status !== 'running')
                                    <form method="POST" action="{{ route('admin.proxy.start', $proxy) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-full bg-slate-900 text-white text-[11px] cursor-pointer">Chạy</button>
                                    </form>
                                @endif
                                @if($proxy->status === 'running')
                                    <form method="POST" action="{{ route('admin.proxy.stop', $proxy) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-full bg-rose-100 text-rose-700 text-[11px] cursor-pointer">Dừng</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.proxy.test', $proxy) }}">
                                    @csrf
                                    <input type="hidden" name="nhamang" value="random">
                                    <input type="hidden" name="tinhthanh" value="0">
                                    <button type="submit" class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[11px] cursor-pointer">Test API</button>
                                </form>
                                <form method="POST" action="{{ route('admin.proxy.rotate', $proxy) }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 text-[11px] cursor-pointer">Xoay IP</button>
                                </form>
                                <button type="button" class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-[11px] cursor-pointer" uk-toggle="target: #proxy-edit-{{ $proxy->id }}">Sửa</button>
                                <form method="POST" action="{{ route('admin.proxy.destroy', $proxy) }}" onsubmit="return confirm('Xóa key này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 text-[11px] cursor-pointer">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-400">Chưa có key nào.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($proxyKeys as $proxy)
        <div id="proxy-edit-{{ $proxy->id }}" uk-modal>
            <div class="uk-modal-dialog uk-modal-body rounded-2xl">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Chỉnh sửa proxy key</h3>
                <form method="POST" action="{{ route('admin.proxy.update', $proxy) }}" class="space-y-3 text-sm">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="text-slate-600 text-xs font-semibold uppercase">Tên key</label>
                        <input type="text" name="label" value="{{ $proxy->label }}" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="text-slate-600 text-xs font-semibold uppercase">API key</label>
                        <textarea name="api_key" rows="3" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2" required>{{ $proxy->api_key }}</textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 text-slate-600">
                        <input type="checkbox" name="is_active" value="1" {{ $proxy->is_active ? 'checked' : '' }} class="rounded border-slate-300 h-4 w-4">
                        Kích hoạt
                    </label>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 cursor-pointer" uk-toggle="target: #proxy-edit-{{ $proxy->id }}">Huỷ</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold cursor-pointer">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
