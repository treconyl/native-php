<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProxyKeyRequest;
use App\Http\Requests\UpdateProxyKeyRequest;
use App\Models\ProxyKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyKeyController extends Controller
{
    public function index()
    {
        $proxyKeys = ProxyKey::orderBy('label')->get();

        return view('admin.proxies', [
            'proxyKeys' => $proxyKeys,
            'activeNav' => 'proxies',
            'title' => 'Proxy Keys',
        ]);
    }

    public function store(StoreProxyKeyRequest $request)
    {
        $data = $request->validated();

        ProxyKey::create([
            'label' => $data['label'],
            'api_key' => $data['api_key'],
            'is_active' => $request->boolean('is_active', true),
            'status' => 'idle',
        ]);

        return back()->with('status', 'Đã thêm key proxy mới.');
    }

    public function update(UpdateProxyKeyRequest $request, ProxyKey $proxy)
    {
        $data = $request->validated();

        $proxy->update([
            'label' => $data['label'],
            'api_key' => $data['api_key'],
            'is_active' => $request->boolean('is_active', $proxy->is_active),
        ]);

        return back()->with('status', 'Đã cập nhật key proxy.');
    }

    public function destroy(ProxyKey $proxy)
    {
        $proxy->delete();

        return back()->with('status', 'Đã xóa key proxy.');
    }

    public function start(ProxyKey $proxy)
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
                $message = $payload['message'] ?? 'Không nhận được thông tin IP.';
                $statusCode = $payload['status'] ?? 'N/A';

                $proxy->update([
                    'status' => 'idle',
                    'is_active' => false,
                    'meta' => array_merge($proxy->meta ?? [], [
                        'last_proxy_response' => $payload,
                    ]),
                ]);

                return back()->withErrors("Không thể khởi động key {$proxy->label} (status {$statusCode}): {$message}");
            }

            $proxy->update([
                'is_active' => true,
                'stop_requested' => false,
                'status' => 'running',
                'last_used_at' => now(),
                'meta' => array_merge($proxy->meta ?? [], [
                    'last_proxy_response' => $payload,
                    'last_proxy_http' => $payload['proxyhttp'] ?? null,
                    'last_proxy_socks' => $payload['proxysocks5'] ?? null,
                    'last_proxy_username' => $payload['username'] ?? null,
                    'last_proxy_password' => $payload['password'] ?? null,
                    'last_proxy_rotated_at' => now()->toDateTimeString(),
                ]),
            ]);

            $message = $payload['message'] ?? 'Proxy đã cấp IP mới.';
            $ipInfo = $payload['proxyhttp'] ?? ($payload['proxysocks5'] ?? '');

            return back()->with('status', "Key {$proxy->label} đã khởi động, IP: {$ipInfo}. {$message}");
        } catch (\Throwable $e) {
            Log::error('Proxy API start failed', [
                'proxy_key_id' => $proxy->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Không thể gọi API proxy để khởi động key: '.$e->getMessage());
        }
    }

    public function stop(ProxyKey $proxy)
    {
        $proxy->update([
            'stop_requested' => true,
            'status' => 'idle',
        ]);

        return back()->with('status', "Đã yêu cầu dừng key {$proxy->label}");
    }

    public function stopAll()
    {
        $stopped = ProxyKey::query()->update([
            'stop_requested' => true,
            'status' => 'idle',
        ]);

        return back()->with('status', "Đã yêu cầu dừng {$stopped} key proxy đang chạy.");
    }

    public function test(ProxyKey $proxy, Request $request)
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
            $message = $data['message'] ?? 'Không có thông điệp trả về.';

            if ($status === 100) {
                return back()->with('status', "Key {$proxy->label} OK: {$message}");
            }

            $proxy->update([
                'status' => 'idle',
                'is_active' => false,
                'meta' => array_merge($proxy->meta ?? [], [
                    'last_proxy_response' => $data,
                ]),
            ]);

            return back()->withErrors("Key {$proxy->label} trả về lỗi ({$status}): {$message}");
        } catch (\Throwable $e) {
            Log::error('Proxy API test failed', [
                'proxy_key_id' => $proxy->id,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Không thể gọi API proxy: '.$e->getMessage());
        }
    }

    public function rotate(ProxyKey $proxy, Request $request)
    {
        $params = [
            'key' => $proxy->api_key,
            'nhamang' => $request->input('nhamang', 'random'),
            'tinhthanh' => $request->input('tinhthanh', 0),
        ];

        try {
            $response = Http::timeout(20)->get('https://proxyxoay.shop/api/get.php', $params);
            $data = $response->json();

            Log::info('Proxy API rotate', [
                'proxy_key_id' => $proxy->id,
                'params' => $params,
                'status_code' => $response->status(),
                'response' => $data,
            ]);

            if (($data['status'] ?? null) !== 100) {
                $message = $data['message'] ?? 'Không có dữ liệu trả về.';
                $statusCode = $data['status'] ?? 'N/A';

                $proxy->update([
                    'status' => 'idle',
                    'is_active' => false,
                    'meta' => array_merge($proxy->meta ?? [], [
                        'last_proxy_response' => $data,
                    ]),
                ]);

                return back()->withErrors("Không thể xoay IP cho key {$proxy->label} (status {$statusCode}): {$message}");
            }

            $ipHttp = $data['proxyhttp'] ?? null;
            $ipSocks = $data['proxysocks5'] ?? null;

            $proxy->update([
                'last_used_at' => now(),
                'meta' => array_merge($proxy->meta ?? [], [
                    'last_proxy_response' => $data,
                    'last_proxy_http' => $ipHttp,
                    'last_proxy_socks' => $ipSocks,
                    'last_proxy_username' => $data['username'] ?? null,
                    'last_proxy_password' => $data['password'] ?? null,
                    'last_proxy_rotated_at' => now()->toDateTimeString(),
                ]),
            ]);

            return back()->with('status', "Đã xoay IP cho {$proxy->label}. HTTP: {$ipHttp}");
        } catch (\Throwable $e) {
            Log::error('Proxy API rotate failed', [
                'proxy_key_id' => $proxy->id,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Không thể xoay IP: '.$e->getMessage());
        }
    }
}
