@extends('layouts.admin', ['activeNav' => 'garena', 'title' => 'Garena Test Runner'])

@section('content')
<header class="flex flex-col gap-2">
    <h2 class="text-3xl font-semibold text-slate-900">Garena Test Runner</h2>
    <p class="text-sm text-slate-500">Chạy Playwright đăng nhập Garena và đổi mật khẩu, theo dõi log bên dưới để xem tiến trình.</p>
</header>

@if (session('status'))
<div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 text-sm">{{ session('status') }}</div>
@endif
@if ($errors->any())
<div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm">{{ $errors->first() }}</div>
@endif

@php
    $selectedAccountId = old('account_id', $credential->account_id ?? null);
    $selectedProxyId = old('proxy_key_id', $credential->proxy_key_id ?? null);
@endphp
<div class="sunrise-card p-6 space-y-4">
    <h3 class="text-base font-semibold text-slate-900">Thông tin đăng nhập Garena</h3>
    <p class="text-xs text-slate-500">Chọn tài khoản đã import, nhập mật khẩu mới và chọn proxy (nếu cần) trước khi chạy Playwright.</p>
    <form method="POST" action="{{ route('admin.garena.credentials') }}" class="space-y-3">
        @csrf
        <div>
            <label class="sunrise-label">Tài khoản Garena</label>
            <select name="account_id" class="sunrise-input mt-1" required>
                <option value="">-- Chọn tài khoản --</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ (string)$selectedAccountId === (string)$account->id ? 'selected' : '' }}>
                        {{ $account->login }} ({{ $account->status }})
                    </option>
                @endforeach
            </select>
            <p class="sunrise-help">Chỉ hiển thị tài khoản đã có mật khẩu hiện tại.</p>
        </div>
        <div>
            <label class="sunrise-label">Mật khẩu mới</label>
            <input type="text" name="new_password" value="{{ old('new_password', $credential->new_password ?? '') }}" class="sunrise-input mt-1" placeholder="Password#2025" required>
            <span class="sunrise-help mt-0">
                Gợi ý: 8-16 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt. Tránh dùng chuỗi dễ đoán như ngày sinh.
            </span>
        </div>
        <div>
            <label class="sunrise-label">Proxy Key</label>
            <select name="proxy_key_id" class="sunrise-input mt-1">
                <option value="">-- Không dùng proxy --</option>
                @foreach($proxyKeys as $proxy)
                    <option value="{{ $proxy->id }}" {{ (string)$selectedProxyId === (string)$proxy->id ? 'selected' : '' }}>
                        {{ $proxy->label }} ({{ $proxy->status ?? 'idle' }})
                    </option>
                @endforeach
            </select>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="headless" value="1" class="rounded border-slate-300 sunrise-checkbox" {{ old('headless') ? 'checked' : '' }}>
            Chạy ẩn (PLAYWRIGHT_HEADLESS)
        </label>
        @if ($credential)
        <p class="text-xs text-slate-400">Đã lưu lần cuối: {{ $credential->updated_at->diffForHumans() }}</p>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <button type="submit" class="btn-sunrise-ghost">Lưu</button>
            <button type="submit"
                    formaction="{{ route('admin.garena.run') }}"
                    class="btn-sunrise">Chạy test</button>
        </div>
        <p class="text-xs text-slate-500">Playwright sẽ dùng tài khoản, mật khẩu mới và proxy (nếu có) ở trên và ghi log vào <code>storage/logs/garena-test.log</code>.</p>
    </form>
</div>

<div class="sunrise-card p-6 w-full overflow-hidden space-y-3">
    <h3 class="font-semibold text-slate-900">Log gần nhất (200 dòng)</h3>
    <div class="overflow-auto">
        <pre class="text-xs bg-slate-900 text-slate-100 rounded-2xl p-4 max-w-full w-full max-h-112 whitespace-pre-wrap break-words">{{ implode("\n", $logLines) }}</pre>
    </div>
</div>
@endsection
