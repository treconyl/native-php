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
        $command = ['php', 'artisan', 'dusk', '--filter=GarenaChangePasswordTest'];
        $env = array_merge($_SERVER, $_ENV, [
            'GARENA_USERNAME' => $this->credentials['username'],
            'GARENA_PASSWORD' => $this->credentials['password'],
            'GARENA_NEW_PASSWORD' => $this->credentials['new_password'] ?? 'Password#2025',
        ]);

        $process = new Process($command, base_path(), $env);
        $process->setTimeout(600);

        Log::channel('garena_test')->info('[Garena Test Job] Bắt đầu chạy Dusk', ['cmd' => implode(' ', $command)]);

        $process->run(function ($type, $buffer) {
            $lines = array_filter(explode("\n", $buffer));
            foreach ($lines as $line) {
                Log::channel('garena_test')->info('[Garena Test Output] ' . $line);
            }
        });

        if (! $process->isSuccessful()) {
            Log::channel('garena_test')->error('[Garena Test Job] Thất bại', ['error' => $process->getErrorOutput()]);
        } else {
            Log::channel('garena_test')->info('[Garena Test Job] Hoàn tất');
        }
    }
}
