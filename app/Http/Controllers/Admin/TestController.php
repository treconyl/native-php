<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPendingAccounts;
use App\Jobs\RunGarenaTest;
use App\Models\Account;
use App\Models\GarenaTestCredential;
use App\Models\ProxyKey;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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
        $proxyKeys = ProxyKey::where('status', '!=', 'expired')->orderBy('label')->get();
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

        if ($proxy && ! $this->rotateProxyOrFail($proxy)) {
            return back()->withErrors('Không thể xoay proxy trước khi chạy. Vui lòng thử lại.');
        }

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
        $proxies = ProxyKey::where('is_active', true)
            ->where('status', 'running')
            ->orderBy('label')
            ->get();

        foreach ($proxies as $proxy) {
            ProcessPendingAccounts::dispatch($proxy->id);
        }

        if ($proxies->isEmpty()) {
            return back()->withErrors('Không có proxy active để chạy.');
        }

        return back()->with('status', "Đã khởi chạy {$proxies->count()} luồng theo proxy active.");
    }

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
            'headless' => ['nullable', 'boolean'],
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
            'headless' => $request->boolean('headless'),
        ];

        return [$account, $proxy, $payload];
    }

    protected function rotateProxyOrFail(?ProxyKey $proxy): bool
    {
        if (! $proxy) {
            return true;
        }

        $meta = $proxy->meta ?? [];
        $lastRotatedAt = isset($meta['last_proxy_rotated_at']) ? Carbon::parse($meta['last_proxy_rotated_at']) : null;
        $cooldownSeconds = 60;

        if ($lastRotatedAt && $lastRotatedAt->gt(now()->subSeconds($cooldownSeconds))) {
            throw ValidationException::withMessages([
                'proxy_key_id' => "Proxy vừa xoay gần đây. Vui lòng đợi khoảng {$cooldownSeconds} giây giữa mỗi lần xoay.",
            ]);
        }

        $params = [
            'key' => $proxy->api_key,
            'nhamang' => 'random',
            'tinhthanh' => 0,
        ];

        try {
            $response = Http::timeout(20)->get('https://proxyxoay.shop/api/get.php', $params);
            $data = $response->json();

            Log::info('[Garena Test] Rotate proxy before run', [
                'proxy_key_id' => $proxy->id,
                'status_code' => $response->status(),
                'response' => $data,
            ]);

            $statusCode = $data['status'] ?? null;

            if ($statusCode !== 100) {
                if (in_array((string) $statusCode, ['101', '102'], true)) {
                    $proxy->update([
                        'status' => 'expired',
                        'is_active' => false,
                        'meta' => array_merge($meta, [
                            'last_proxy_response' => $data,
                        ]),
                    ]);
                }

                throw ValidationException::withMessages([
                    'proxy_key_id' => 'Không xoay được IP (status '.($statusCode ?? 'N/A').'). '.($data['message'] ?? ''),
                ]);
            }

            $proxy->update([
                'last_used_at' => now(),
                'status' => 'running',
                'meta' => array_merge($meta, [
                    'last_proxy_response' => $data,
                    'last_proxy_http' => $data['proxyhttp'] ?? null,
                    'last_proxy_socks' => $data['proxysocks5'] ?? null,
                    'last_proxy_username' => $data['username'] ?? null,
                    'last_proxy_password' => $data['password'] ?? null,
                    'last_proxy_rotated_at' => now()->toDateTimeString(),
                ]),
            ]);

            return true;
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('[Garena Test] Rotate proxy failed before run', [
                'proxy_key_id' => $proxy->id,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'proxy_key_id' => 'Không thể xoay proxy trước khi chạy: '.$e->getMessage(),
            ]);
        }
    }
}
