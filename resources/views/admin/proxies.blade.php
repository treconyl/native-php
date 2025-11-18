@extends('layouts.admin', ['activeNav' => 'proxies', 'title' => 'Proxy Keys'])

@section('content')
    <header class="flex flex-col gap-2">
        <h2 class="text-3xl font-semibold text-slate-900">Proxy Keys</h2>
        <p class="text-sm text-slate-500">Quản lý key xoay IP và trạng thái worker.</p>
    </header>

    <div class="rounded-2xl bg-white shadow-sm border border-white p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">Tổng {{ $proxyKeys->count() }} key</h3>
        </div>
        <div class="rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Tên</th>
                    <th class="px-4 py-3 text-left">API Key</th>
                    <th class="px-4 py-3 text-left">Trạng thái</th>
                    <th class="px-4 py-3 text-left">Lần dùng gần nhất</th>
                </tr>
                </thead>
                <tbody>
                @forelse($proxyKeys as $proxy)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $proxy->label }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($proxy->api_key, 40) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs {{ $proxy->status === 'running' ? 'bg-emerald-100 text-emerald-700' : ($proxy->status === 'stopping' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                {{ ucfirst($proxy->status ?? 'idle') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ optional($proxy->last_used_at)->format('d/m/Y H:i') ?? 'Chưa sử dụng' }}</td>
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
@endsection
