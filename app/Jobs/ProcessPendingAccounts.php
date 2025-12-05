<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\ProxyKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessPendingAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $proxyKeyId) {}

    public function handle(): void
    {
        $proxy = ProxyKey::find($this->proxyKeyId);

        if (! $proxy || ! $proxy->is_active) {
            return;
        }

        if (! $this->rotateProxy($proxy)) {
            return;
        }

        $account = null;
        $generatedPassword = null;

        DB::transaction(function () use (&$account, &$generatedPassword) {
            $account = Account::where('status', 'pending')
                ->whereNotNull('current_password')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($account) {
                $generatedPassword = $this->generatePassword();
                $account->update([
                    'status' => 'processing',
                    'last_attempted_at' => now(),
                    'last_error' => null,
                    'next_password' => $generatedPassword,
                ]);
            }
        }, 3);

        if (! $account) {
            return;
        }

        $newPassword = $generatedPassword ?: ($account->next_password ?: config('services.garena.default_new_password', 'Password#2025'));

        RunGarenaTest::dispatch([
            'account_id' => $account->id,
            'username' => $account->login,
            'password' => $account->current_password,
            'new_password' => $newPassword,
            'proxy_key_id' => $proxy->id,
            'proxy_label' => $proxy->label,
            'multi_run' => true,
        ]);
    }

    protected function rotateProxy(ProxyKey $proxy): bool
    {
        $meta = $proxy->meta ?? [];
        $lastRotatedAt = isset($meta['last_proxy_rotated_at']) ? Carbon::parse($meta['last_proxy_rotated_at']) : null;
        $cooldownSeconds = 60;

        if ($lastRotatedAt && $lastRotatedAt->gt(now()->subSeconds($cooldownSeconds))) {
            $wait = max(5, $cooldownSeconds - now()->diffInSeconds($lastRotatedAt));
            $this->release($wait);

            return false;
        }

        $params = [
            'key' => $proxy->api_key,
            'nhamang' => 'random',
            'tinhthanh' => 0,
        ];

        try {
            $response = Http::timeout(20)->get('https://proxyxoay.shop/api/get.php', $params);
            $data = $response->json();

            Log::info('Proxy rotate before job', [
                'proxy_key_id' => $proxy->id,
                'status_code' => $response->status(),
                'response' => $data,
            ]);

            if (($data['status'] ?? null) !== 100) {
                $wait = 20;
                $this->release($wait);

                return false;
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
        } catch (\Throwable $e) {
            Log::error('Proxy rotate failed before job', [
                'proxy_key_id' => $proxy->id,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            $this->release(20);

            return false;
        }
    }

    protected function generatePassword(): string
    {
        $length = random_int(10, 12);
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $special = '@#$%&*?!';

        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $special[random_int(0, strlen($special) - 1)],
        ];

        $pool = $upper.$lower.$digits.$special;

        while (count($chars) < $length) {
            $chars[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        return Str::shuffle(implode('', $chars));
    }
}
