<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPendingAccounts;
use App\Jobs\RunGarenaTest;
use App\Models\Account;
use App\Models\GarenaTestCredential;
use App\Models\ProxyKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TestController extends Controller
{
    public function garena()
    {
        $logPath = storage_path('logs/garena-test.log');
        $logLines = file_exists($logPath)
            ? array_slice(file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -200)
            : [];

        $accounts = Account::whereNotNull('current_password')
            ->orderBy('login')
            ->get();
        $proxyKeys = ProxyKey::orderBy('label')->get();
        $credential = GarenaTestCredential::first();

        return view('admin.garena.index', [
            'logLines' => $logLines,
            'credential' => $credential,
            'accounts' => $accounts,
            'proxyKeys' => $proxyKeys,
            'activeNav' => 'garena',
            'title' => 'Garena Test Runner',
        ]);
    }

    public function runGarena(Request $request)
    {
        [$account, $proxy, $payload] = $this->prepareCredentialPayload($request);

        GarenaTestCredential::updateOrCreate([], $payload);

        $logPath = storage_path('logs/garena-test.log');
        file_put_contents($logPath, '');

        $jobPayload = [
            'account_id' => $payload['account_id'],
            'username' => $payload['username'],
            'password' => $payload['password'],
            'new_password' => $payload['new_password'] ?? 'Password#2025',
            'proxy_key_id' => $payload['proxy_key_id'],
            'proxy_label' => $proxy?->label,
        ];

        RunGarenaTest::dispatch($jobPayload);

        Log::channel('garena_test')->info('[Garena Test] Đã xếp hàng job Playwright từ giao diện.');

        return back()->with('status', 'Đã gửi job Garena Playwright, xem log bên dưới để theo dõi tiến trình.');
    }

    public function saveGarenaCredentials(Request $request)
    {
        [$account, $proxy, $payload] = $this->prepareCredentialPayload($request);

        $credential = GarenaTestCredential::first();

        if ($credential) {
            $credential->update($payload);
        } else {
            GarenaTestCredential::create($payload);
        }

        Log::channel('garena_test')->info('[Garena Test] Đã cập nhật thông tin đăng nhập.');

        return back()->with('status', 'Đã lưu thông tin đăng nhập Garena.');
    }

    public function runGarenaMulti(Request $request)
    {
        $proxies = ProxyKey::where('is_active', true)->orderBy('label')->get();

        foreach ($proxies as $proxy) {
            ProcessPendingAccounts::dispatch($proxy->id);
        }

        if ($proxies->isEmpty()) {
            return back()->withErrors('Không có proxy active để chạy.');
        }

        return back()->with('status', "Đã khởi chạy {$proxies->count()} luồng theo proxy active.");
    }

    /**
     * Validate and assemble payload for Garena credentials.
     *
     * @return array{0:\App\Models\Account,1:\App\Models\ProxyKey|null,2:array<string,mixed>}
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function prepareCredentialPayload(Request $request): array
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'max:16',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
            ],
            'proxy_key_id' => ['nullable', 'exists:proxy_keys,id'],
        ], [
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'new_password.regex' => 'Mật khẩu mới phải dài 8-16 ký tự và bao gồm chữ hoa, chữ thường, chữ số và ký tự đặc biệt.',
        ]);

        $account = Account::findOrFail($data['account_id']);

        if (blank($account->current_password)) {
            throw ValidationException::withMessages([
                'account_id' => 'Tài khoản đã chọn chưa có mật khẩu hiện tại.',
            ]);
        }

        $proxy = null;
        if (! empty($data['proxy_key_id'])) {
            $proxy = ProxyKey::findOrFail($data['proxy_key_id']);
        }

        $payload = [
            'account_id' => $account->id,
            'username' => $account->login,
            'password' => $account->current_password,
            'new_password' => $data['new_password'],
            'proxy_key_id' => $proxy?->id,
        ];

        return [$account, $proxy, $payload];
    }
}
