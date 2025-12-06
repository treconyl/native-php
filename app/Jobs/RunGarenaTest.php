<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

use App\Jobs\ProcessPendingAccounts;

use App\Models\Account;

class RunGarenaTest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $credentials) {}

    public function handle(): void
    {
        $script = base_path('playwright/garena-runner.js');
        $command = ['node', $script];
        $headlessRequested = array_key_exists('headless', $this->credentials)
            ? ($this->credentials['headless'] ? 'true' : 'false')
            : env('PLAYWRIGHT_HEADLESS', 'false');
        $env = array_merge($_SERVER, $_ENV, [
            'GARENA_USERNAME' => $this->credentials['username'],
            'GARENA_PASSWORD' => $this->credentials['password'],
            'GARENA_NEW_PASSWORD' => $this->credentials['new_password'] ?? 'Password#2025',
            'PLAYWRIGHT_HEADLESS' => $headlessRequested,
            'GARENA_ACCOUNT_ID' => $this->credentials['account_id'] ?? $this->credentials['username'],
        ]);

        if (! empty($this->credentials['proxy_key_id'])) {
            $env['PLAYWRIGHT_PROXY_KEY_ID'] = (string) $this->credentials['proxy_key_id'];
            $env['PLAYWRIGHT_PROXY_LABEL'] = $this->credentials['proxy_label'] ?? '';
        }

        $this->updateAccount([
            'status' => 'processing',
            'last_attempted_at' => now(),
            'last_error' => null,
        ]);

        $process = new Process($command, base_path(), $env);
        $process->setTimeout(600);

        Log::channel('garena_test')->info('[Garena Test Job] Bắt đầu chạy Playwright', [
            'cmd' => implode(' ', $command),
            'proxy_key_id' => $this->credentials['proxy_key_id'] ?? null,
            'proxy_label' => $this->credentials['proxy_label'] ?? null,
        ]);

        $process->run(function ($type, $buffer) {
            $lines = array_filter(explode("\n", $buffer));
            foreach ($lines as $line) {
                Log::channel('garena_test')->info('[Garena Test Output] ' . $line);
            }
        });

        if (! $process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            $friendlyError = $this->summarizeError($errorOutput);

            Log::channel('garena_test')->error('[Garena Test Job] Thất bại', [
                'error' => $errorOutput,
                'summary' => $friendlyError,
            ]);

            $this->updateAccount([
                'status' => 'failed',
                'last_attempted_at' => now(),
                'last_error' => $friendlyError,
                'next_password' => null,
            ]);

            $this->queueNext();

            return;
        }

        $this->updateAccount([
            'status' => 'success',
            'current_password' => $this->credentials['new_password'] ?? null,
            'next_password' => $this->credentials['new_password'] ?? null,
            'last_attempted_at' => now(),
            'last_succeeded_at' => now(),
            'last_error' => null,
        ]);

        Log::channel('garena_test')->info('[Garena Test Job] Hoàn tất');

        $this->queueNext();
    }

    protected function updateAccount(array $attributes): void
    {
        $accountId = $this->credentials['account_id'] ?? null;

        if (! $accountId) {
            return;
        }

        Account::whereKey($accountId)->update($attributes);
    }

    protected function queueNext(): void
    {
        if (! ($this->credentials['multi_run'] ?? false)) {
            return;
        }

        $proxyId = $this->credentials['proxy_key_id'] ?? null;

        if ($proxyId) {
            ProcessPendingAccounts::dispatch($proxyId)->onQueue($this->queue);
        }
    }

    protected function summarizeError(string $errorOutput): string
    {
        $lower = mb_strtolower($errorOutput);

        if (str_contains($lower, 'captcha') || str_contains($lower, 'xác minh')) {
            return 'Garena yêu cầu captcha/xác minh, dừng lại.';
        }

        if (str_contains($lower, 'nguy hiểm') || str_contains($lower, 'tai khoan nguy hiem')) {
            return 'Garena báo tài khoản nguy hiểm, dừng lại.';
        }

        if (str_contains($lower, 'browser has been closed') || str_contains($lower, 'target page') || str_contains($lower, 'context or browser')) {
            return 'Trình duyệt bị đóng đột ngột khi chạy.';
        }

        if (str_contains($lower, 'timeout')) {
            return 'Hết thời gian chờ khi thao tác Garena.';
        }

        if (str_contains($lower, 'net::') || str_contains($lower, 'network')) {
            return 'Lỗi mạng khi truy cập Garena.';
        }

        $firstLine = trim(strtok($errorOutput, "\n")) ?: 'Lỗi Playwright không xác định.';

        return Str::limit('[Playwright] ' . $firstLine, 220);
    }
}
