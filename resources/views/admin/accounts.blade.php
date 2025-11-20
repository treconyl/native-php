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
                <button type="button" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-600 hover:bg-white cursor-pointer" uk-toggle="target: #account-create-modal">
                    Thêm thủ công
                </button>
                <a href="{{ route('admin.accounts.export') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-600 hover:bg-white cursor-pointer">Xuất CSV</a>
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
                                    'processing' => 'bg-blue-100 text-blue-700',
                                ];
                                $pillClass = $statusStyles[$status] ?? 'bg-slate-100 text-slate-600';
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $pillClass }}">
                                {{ $account->status }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-slate-500">{{ optional($account->last_attempted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-3 py-3 text-slate-500">
                            <div class="flex items-center gap-2">
                                <span>{{ \Illuminate\Support\Str::limit($account->last_error ?? '-', 40) }}</span>
                                <button type="button" class="text-xs px-3 py-1 rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer" uk-toggle="target: #account-edit-{{ $account->id }}">
                                    Sửa
                                </button>
                            </div>
                        </td>
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

    <div id="account-create-modal" uk-modal>
        <div class="uk-modal-dialog uk-modal-body rounded-2xl">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Thêm tài khoản</h3>
            <form method="POST" action="{{ route('admin.accounts.store') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="text-slate-600 text-xs font-semibold uppercase">Login</label>
                    <input type="text" name="login" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2" required>
                </div>
                <div>
                    <label class="text-slate-600 text-xs font-semibold uppercase">Mật khẩu hiện tại</label>
                    <input type="text" name="current_password" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-slate-600 text-xs font-semibold uppercase">Mật khẩu mới</label>
                        <input type="text" name="next_password" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="text-slate-600 text-xs font-semibold uppercase">Trạng thái</label>
                        <select name="status" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2">
                            <option value="pending">pending</option>
                            <option value="processing">processing</option>
                            <option value="success">success</option>
                            <option value="failed">failed</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-slate-600 text-xs font-semibold uppercase">Lỗi gần nhất (tuỳ chọn)</label>
                    <textarea name="last_error" rows="2" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 cursor-pointer" uk-toggle="target: #account-create-modal">Huỷ</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold cursor-pointer">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($accounts as $account)
        <div id="account-edit-{{ $account->id }}" uk-modal>
            <div class="uk-modal-dialog uk-modal-body rounded-2xl">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Chỉnh sửa tài khoản</h3>
                <form method="POST" action="{{ route('admin.accounts.update', $account) }}" class="space-y-3 text-sm">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="text-slate-600 text-xs font-semibold uppercase">Login</label>
                        <input type="text" name="login" value="{{ $account->login }}" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="text-slate-600 text-xs font-semibold uppercase">Mật khẩu hiện tại</label>
                        <input type="text" name="current_password" value="{{ $account->current_password }}" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2" required>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-slate-600 text-xs font-semibold uppercase">Mật khẩu mới</label>
                            <input type="text" name="next_password" value="{{ $account->next_password }}" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2">
                        </div>
                        <div>
                            <label class="text-slate-600 text-xs font-semibold uppercase">Trạng thái</label>
                            @php($statusValue = strtolower($account->status ?? 'pending'))
                            <select name="status" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2">
                                @foreach(['pending', 'processing', 'success', 'failed'] as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($statusValue === $statusOption)>{{ $statusOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-slate-600 text-xs font-semibold uppercase">Lỗi gần nhất (tuỳ chọn)</label>
                        <textarea name="last_error" rows="2" class="w-full mt-1 rounded-xl border border-slate-200 px-3 py-2">{{ $account->last_error }}</textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 cursor-pointer" uk-toggle="target: #account-edit-{{ $account->id }}">Huỷ</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold cursor-pointer">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
