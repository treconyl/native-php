@extends('layouts.admin', ['activeNav' => 'tests', 'title' => 'Garena Test Runner'])

@section('content')
<header class="flex flex-col gap-2">
    <h2 class="text-3xl font-semibold text-slate-900">Garena Test Runner</h2>
    <p class="text-sm text-slate-500">Chạy Dusk test đăng nhập Garena và dừng lại trước bước đổi mật khẩu. Theo dõi log bên dưới để xem từng hành động.</p>
</header>

@if (session('status'))
<div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 text-sm">{{ session('status') }}</div>
@endif
@if ($errors->any())
<div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm">{{ $errors->first() }}</div>
@endif

<div class="rounded-2xl bg-white shadow-sm border border-white p-6 space-y-4">
    <h3 class="text-base font-semibold text-slate-900">Thông tin đăng nhập Garena</h3>
    <p class="text-xs text-slate-500">Thông tin được mã hóa trong cơ sở dữ liệu và chỉ dùng nội bộ cho bài test.</p>
    <form method="POST" action="{{ route('admin.tests.garena.credentials') }}" class="space-y-3">
        @csrf
        <div>
            <label class="text-xs text-slate-500 font-semibold uppercase">Username</label>
            <input type="text" name="username" value="{{ old('username', $credential->username ?? '') }}" class="mt-1 p-3 w-full rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-slate-900 text-sm" placeholder="vd: Jaso.uc30" required>
        </div>
        <div>
            <label class="text-xs text-slate-500 font-semibold uppercase">Mật khẩu hiện tại</label>
            <input type="password" name="password" value="{{ old('password') }}" class="mt-1 p-3 border w-full rounded-xl border-slate-200 focus:border-slate-900 focus:ring-slate-900 text-sm" required>
        </div>
        <div>
            <label class="text-xs text-slate-500 font-semibold uppercase">Mật khẩu mới</label>
            <input type="password" name="new_password" value="{{ old('new_password') }}" class="mt-1 p-3 border w-full rounded-xl border-slate-200 focus:border-slate-900 focus:ring-slate-900 text-sm" required>
        </div>
        @if ($credential)
        <p class="text-xs text-slate-400">Đã lưu lần cuối: {{ $credential->updated_at->diffForHumans() }}</p>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold cursor-pointer">Lưu</button>
            <button type="submit"
                    formaction="{{ route('admin.tests.garena.run') }}"
                    class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold cursor-pointer">Chạy test</button>
        </div>
        <p class="text-xs text-slate-500">Test sử dụng thông tin trên và ghi log vào <code>storage/logs/garena-test.log</code>.</p>
    </form>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-white p-6 w-full max-w-[calc(100vw-20rem)]">
    <h3 class="font-semibold text-slate-900 mb-4">Log gần nhất (200 dòng)</h3>
    <div class="overflow-x-auto">
        <pre class="text-xs bg-slate-900 text-slate-100 rounded-2xl p-4 max-w-full w-full max-h-112">{{ implode("\n", $logLines) }}</pre>
    </div>
</div>
@endsection
