<?php

namespace App\Jobs;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class RunGarenaTest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $credentials) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $script = base_path('playwright/garena-runner.js');
        $command = ['node', $script];
        $env = array_merge($_SERVER, $_ENV, [
            'GARENA_USERNAME' => $this->credentials['username'],
            'GARENA_PASSWORD' => $this->credentials['password'],
            'GARENA_NEW_PASSWORD' => $this->credentials['new_password'] ?? 'Password#2025',
            'PLAYWRIGHT_HEADLESS' => env('PLAYWRIGHT_HEADLESS', 'false'),
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

            Log::channel('garena_test')->error('[Garena Test Job] Thất bại', [
                'error' => $errorOutput,
            ]);

            $this->updateAccount([
                'status' => 'failed',
                'last_attempted_at' => now(),
                'last_error' => Str::limit(trim($errorOutput), 1000),
            ]);

            return;
        }

        $this->updateAccount([
            'status' => 'success',
            'current_password' => null,
            'next_password' => $this->credentials['new_password'] ?? null,
            'last_attempted_at' => now(),
            'last_succeeded_at' => now(),
            'last_error' => null,
        ]);

        Log::channel('garena_test')->info('[Garena Test Job] Hoàn tất');
    }

    protected function updateAccount(array $attributes): void
    {
        $accountId = $this->credentials['account_id'] ?? null;

        if (! $accountId) {
            return;
        }

        Account::whereKey($accountId)->update($attributes);
    }
}
