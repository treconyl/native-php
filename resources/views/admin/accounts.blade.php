@extends('layouts.admin', ['activeNav' => 'accounts', 'title' => 'Danh sách tài khoản'])

@section('content')
    <header class="flex flex-col gap-2">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-3xl font-semibold text-slate-900">Danh sách tài khoản</h2>
                <p class="text-sm text-slate-500">Quản lý và theo dõi từng tài khoản đã import vào hệ thống.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.accounts.import') }}" enctype="multipart/form-data" class="flex items-center gap-2 text-xs text-slate-600 border border-dashed border-slate-300 px-3 py-2 rounded-xl bg-white">
                    @csrf
                    <label class="cursor-pointer text-slate-500">
                        Import
                        <input type="file" name="file" accept=".txt,.csv" class="hidden" onchange="this.form.submit()">
                    </label>
                    <span class="text-[11px] text-slate-400">login|password</span>
                </form>
                <a href="{{ route('admin.accounts.export') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-600 hover:bg-white">Xuất CSV</a>
            </div>
        </div>
        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700 text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm">{{ $errors->first() }}</div>
        @endif
    </header>

    <div class="rounded-2xl bg-white shadow-sm border border-white p-6 space-y-4">
        <div class="flex items-center justify-between text-sm text-slate-600">
            <span>Tổng {{ $accounts->total() }} tài khoản</span>
        </div>
        <div class="rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-3 text-left">Login</th>
                    <th class="px-3 py-3 text-left">Mật khẩu hiện tại</th>
                    <th class="px-3 py-3 text-left">Mật khẩu mới</th>
                    <th class="px-3 py-3 text-left">Trạng thái</th>
                    <th class="px-3 py-3 text-left">Lần thử gần nhất</th>
                    <th class="px-3 py-3 text-left">Lỗi gần nhất</th>
                </tr>
                </thead>
                <tbody>
                @forelse($accounts as $account)
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-3 font-medium text-slate-900">{{ $account->login }}</td>
                        <td class="px-3 py-3">{{ $account->current_password ? \Illuminate\Support\Str::limit($account->current_password, 30) : 'Chưa nhập' }}</td>
                        <td class="px-3 py-3">{{ $account->next_password ? \Illuminate\Support\Str::limit($account->next_password, 30) : 'Chưa đổi' }}</td>
                        <td class="px-3 py-3">
                            @php
                                $status = strtolower($account->status ?? 'pending');
                                $statusStyles = [
                                    'success' => 'bg-emerald-100 text-emerald-800',
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'failed' => 'bg-rose-100 text-rose-700',
                                ];
                                $pillClass = $statusStyles[$status] ?? 'bg-slate-100 text-slate-600';
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $pillClass }}">
                                {{ $account->status }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-slate-500">{{ optional($account->last_attempted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-3 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($account->last_error ?? '-', 40) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-slate-400">Chưa có tài khoản nào.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>
            {{ $accounts->links() }}
        </div>
    </div>
@endsection
