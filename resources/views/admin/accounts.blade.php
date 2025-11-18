@extends('layouts.admin', ['activeNav' => 'accounts', 'title' => 'Danh sách tài khoản'])

@section('content')
    <header class="flex flex-col gap-2">
        <h2 class="text-3xl font-semibold text-slate-900">Danh sách tài khoản</h2>
        <p class="text-sm text-slate-500">Quản lý và theo dõi từng tài khoản được import vào hệ thống.</p>
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
                    <th class="px-3 py-3 text-left">Lần thử cuối</th>
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
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-slate-100 text-slate-600">{{ $account->status }}</span>
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
