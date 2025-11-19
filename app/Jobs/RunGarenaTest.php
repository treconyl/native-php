<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
        ]);

        if (! empty($this->credentials['proxy_key_id'])) {
            $env['PLAYWRIGHT_PROXY_KEY_ID'] = (string) $this->credentials['proxy_key_id'];
            $env['PLAYWRIGHT_PROXY_LABEL'] = $this->credentials['proxy_label'] ?? '';
        }

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
            Log::channel('garena_test')->error('[Garena Test Job] Thất bại', [
                'error' => $process->getErrorOutput() ?: $process->getOutput(),
            ]);

            return;
        }

        Log::channel('garena_test')->info('[Garena Test Job] Hoàn tất');
    }
}
