@extends('layouts.admin', ['activeNav' => 'proxies', 'title' => 'Proxy Keys'])

@section('content')
<header>
    <h2 class="text-3xl font-semibold text-slate-900 mb-0!">Danh sách Proxy</h2>
    <p class="text-sm text-slate-500">Quản lý key xoay IP và trạng thái worker.</p>
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 text-sm">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm">{{ $errors->first() }}
        </div>
    @endif
</header>

<div class="sunrise-card p-5 space-y-5">
    <div class="grid gap-3 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
            <h3 class="font-semibold text-slate-900">Thêm proxy key</h3>
            <form method="POST" action="{{ route('admin.proxy.store') }}" class="flex flex-col gap-2 text-sm">
                @csrf
                <div>
                    <label class="sunrise-label">Tên key</label>
                    <input type="text" name="label" class="sunrise-input mt-1 text-sm" placeholder="Key proxy.vn"
                        required>
                </div>
                <div>
                    <label class="sunrise-label">API key</label>
                    <textarea name="api_key" rows="3" class="sunrise-input mt-1 text-sm" placeholder="xxxx-xxxx-xxxx"
                        required></textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-slate-600 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 h-4 w-4">
                    Kích hoạt ngay
                </label>
                <button type="submit" class="btn-sunrise justify-center">Lưu key</button>
            </form>
        </div>
        <div class="rounded-2xl border border-slate-200 p-4 space-y-2">
            <h3 class="font-semibold text-slate-900">Thống kê</h3>
            <p class="text-sm text-slate-500">Tổng {{ $proxyKeys->count() }} key,
                {{ $proxyKeys->where('status', 'running')->count() }} đang chạy.
            </p>
            <p class="text-xs text-slate-400">Nhớ xoay IP sau mỗi phút để tránh bị chặn.</p>
        </div>
    </div>
    <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white">
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
                @php($isExpiredStatus = $proxy->status === 'expired')
                @php($isRunning = $proxy->status === 'running' && !$isExpiredStatus)
                @php($statusClass = $isExpiredStatus ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-900')
                @php($statusLabel = $isExpiredStatus ? 'Hết hạn' : 'Đang sử dụng')
                @php($ipDisplay = $currentHttp ? $currentHttp . (($proxyUser && $proxyPass) ? ':' . $proxyUser . ':' . $proxyPass : '') : null)
                <tr class="border-t border-slate-100">
                    <td class="px-3 py-2 font-medium text-slate-900">{{ $proxy->label }}</td>
                    <td class="px-3 py-2 text-slate-500">{{ \Illuminate\Support\Str::limit($proxy->api_key, 36) }}</td>
                    <td class="px-3 py-2">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-xs text-slate-600">
                        @if($ipDisplay)
                            <div class="font-mono">{{ $ipDisplay }}</div>
                            <div class="text-[11px] text-slate-400">Cập nhật:
                                {{ $rotatedAt?->format('d/m/Y H:i:s') ?? 'Chưa có' }}
                            </div>
                        @else
                            <span class="text-slate-400">Chưa có IP</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-sm text-slate-600">
                        {{ optional($proxy->last_used_at)->format('d/m/Y H:i') ?? 'Chưa sử dụng' }}
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex flex-wrap gap-1.5">
                            <form method="POST" action="{{ route('admin.proxy.test', $proxy) }}">
                                @csrf
                                <input type="hidden" name="nhamang" value="random">
                                <input type="hidden" name="tinhthanh" value="0">
                                <button type="submit" class="sunrise-chip sunrise-chip--ghost">Kiểm tra</button>
                            </form>
                            <form method="POST" action="{{ route('admin.proxy.rotate', $proxy) }}">
                                @csrf
                                <button type="submit" class="sunrise-chip">Xoay</button>
                            </form>
                            <button type="button" class="sunrise-chip sunrise-chip--ghost"
                                uk-toggle="target: #proxy-edit-{{ $proxy->id }}">Sửa</button>
                            <form method="POST" action="{{ route('admin.proxy.destroy', $proxy) }}"
                                onsubmit="return confirm('Xóa key này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sunrise-chip sunrise-chip--rose">Xóa</button>
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
                    <input type="text" name="label" value="{{ $proxy->label }}"
                        class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2" required>
                </div>
                <div>
                    <label class="text-slate-600 text-xs font-semibold uppercase">API key</label>
                    <textarea name="api_key" rows="3" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2"
                        required>{{ $proxy->api_key }}</textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-slate-600">
                    <input type="checkbox" name="is_active" value="1" {{ $proxy->is_active ? 'checked' : '' }}
                        class="rounded border-slate-300 h-4 w-4">
                    Kích hoạt
                </label>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="btn-sunrise-ghost"
                        uk-toggle="target: #proxy-edit-{{ $proxy->id }}">Huỷ</button>
                    <button type="submit" class="btn-sunrise">Lưu</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection