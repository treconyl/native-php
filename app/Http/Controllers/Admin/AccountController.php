<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ProxyKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::orderByDesc('id')->paginate(25);
        $proxyKeys = ProxyKey::orderBy('label')->get();

        $stats = [
            'total' => Account::count(),
            'success' => Account::where('status', 'success')->count(),
            'failed' => Account::where('status', 'failed')->count(),
            'pending' => Account::where('status', 'pending')->count(),
            'latest_error' => Account::whereNotNull('last_error')
                ->orderByDesc('last_attempted_at')
                ->value('last_error'),
            'proxies_running' => $proxyKeys->where('status', 'running')->count(),
            'proxies_total' => $proxyKeys->count(),
        ];

        return view('admin.dashboard', [
            'accounts' => $accounts,
            'proxyKeys' => $proxyKeys,
            'stats' => $stats,
            'activeNav' => 'dashboard',
            'title' => 'Tổng quan hệ thống',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:txt,csv'],
        ]);

        $file = $request->file('file');

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        $handle = fopen($file->getRealPath(), 'r');

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $login = $password = null;

            if (str_contains($line, '|')) {
                [$login, $password] = array_map('trim', explode('|', $line, 2));
            } else {
                $parts = array_map('trim', str_getcsv($line));
                $login = $parts[0] ?? '';
                $password = $parts[1] ?? '';
            }

            if ($login === '' || $password === '') {
                $skipped++;
                continue;
            }

            $account = Account::updateOrCreate(
                ['login' => $login],
                [
                    'current_password' => $password,
                    'status' => 'pending',
                    'last_error' => null,
                ]
            );

            $account->wasRecentlyCreated ? $inserted++ : $updated++;
        }

        fclose($handle);

        return redirect()->route('admin.dashboard')
            ->with('status', "Import hoàn tất: {$inserted} mới, {$updated} cập nhật, {$skipped} bỏ qua.");
    }

    public function export()
    {
        $filename = 'accounts_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['login', 'current_password', 'status', 'last_attempted_at', 'last_error']);

            Account::orderBy('id')->chunk(500, function ($chunk) use ($handle) {
                foreach ($chunk as $account) {
                    fputcsv($handle, [
                        $account->login,
                        $account->current_password,
                        $account->status,
                        optional($account->last_attempted_at)->toDateTimeString(),
                        $account->last_error,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function storeProxyKey(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'api_key' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ProxyKey::create([
            'label' => $data['label'],
            'api_key' => $data['api_key'],
            'is_active' => $request->boolean('is_active', true),
            'status' => 'idle',
        ]);

        return redirect()->route('admin.dashboard')->with('status', 'Đã thêm key proxy mới.');
    }

    public function startProxy(ProxyKey $proxy)
    {
        $params = [
            'key' => $proxy->api_key,
            'nhamang' => 'random',
            'tinhthanh' => 0,
        ];

        try {
            $response = Http::timeout(20)->get('https://proxyxoay.shop/api/get.php', $params);
            $payload = $response->json();

            Log::info('Proxy API start request', [
                'proxy_key_id' => $proxy->id,
                'status_code' => $response->status(),
                'response' => $payload,
            ]);

            if (($payload['status'] ?? null) !== 100) {
                $message = $payload['message'] ?? __('Không nhận được thông tin IP.');

                return back()->withErrors("Không thể khởi động key {$proxy->label}: {$message}");
            }

            $proxy->update([
                'is_active' => true,
                'stop_requested' => false,
                'status' => 'running',
                'last_used_at' => now(),
                'meta' => array_merge($proxy->meta ?? [], [
                    'last_proxy_response' => $payload,
                ]),
            ]);

            $message = $payload['message'] ?? __('Proxy đã cấp IP mới.');
            $ipInfo = $payload['proxyhttp'] ?? ($payload['proxysocks5'] ?? '');

            // TODO: dispatch job to actually run Dusk with this key.

            return redirect()->route('admin.dashboard')
                ->with('status', "Key {$proxy->label} đã khởi động, IP: {$ipInfo}. {$message}");
        } catch (\Throwable $e) {
            Log::error('Proxy API start failed', [
                'proxy_key_id' => $proxy->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Không thể gọi API proxy để khởi động key: ' . $e->getMessage());
        }
    }

    public function stopProxy(ProxyKey $proxy)
    {
        $proxy->update([
            'stop_requested' => true,
            'status' => 'stopping',
        ]);

        return redirect()->route('admin.dashboard')->with('status', "Đã yêu cầu dừng key {$proxy->label}");
    }

    public function testProxy(ProxyKey $proxy, Request $request)
    {
        $params = [
            'key' => $proxy->api_key,
            'nhamang' => $request->input('nhamang', 'random'),
            'tinhthanh' => $request->input('tinhthanh', 0),
        ];

        try {
            $response = Http::timeout(20)->get('https://proxyxoay.shop/api/get.php', $params);
            $data = $response->json();

            Log::info('Proxy API test', [
                'proxy_key_id' => $proxy->id,
                'params' => $params,
                'status_code' => $response->status(),
                'response' => $data,
            ]);

            $status = $data['status'] ?? null;
            $message = $data['message'] ?? __('Không có thông điệp trả về.');

            if ($status === 100) {
                return back()->with('status', "Key {$proxy->label} OK: {$message}");
            }

            return back()->withErrors("Key {$proxy->label} trả về lỗi ({$status}): {$message}");
        } catch (\Throwable $e) {
            Log::error('Proxy API test failed', [
                'proxy_key_id' => $proxy->id,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Không thể gọi API proxy: ' . $e->getMessage());
        }
    }

    public function proxiesIndex()
    {
        $proxyKeys = ProxyKey::orderBy('label')->get();

        return view('admin.proxies', [
            'proxyKeys' => $proxyKeys,
            'activeNav' => 'proxies',
            'title' => 'Proxy Keys',
        ]);
    }

    public function accountList()
    {
        $accounts = Account::orderByDesc('id')->paginate(30);

        return view('admin.accounts', [
            'accounts' => $accounts,
            'activeNav' => 'accounts',
            'title' => 'Danh sách tài khoản',
        ]);
    }
}
