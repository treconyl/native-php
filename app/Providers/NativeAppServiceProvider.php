<?php

namespace App\Providers;

use Illuminate\Filesystem\Filesystem;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;
use Symfony\Component\Process\Process;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        $this->ensureQueueWorkers(5);

        Window::open()
            ->title('Garena Tool')
            ->width(1600)
            ->height(800);
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }

    protected function ensureQueueWorkers(int $maxWorkers = 5): void
    {
        $filesystem = app(Filesystem::class);
        $pidFile = storage_path('app/queue-native-pids.json');
        $pids = [];

        if ($filesystem->exists($pidFile)) {
            $pids = json_decode($filesystem->get($pidFile), true) ?: [];
        }

        $pids = array_values(array_filter($pids, fn ($pid) => $this->isProcessRunning((int) $pid)));

        while (count($pids) < $maxWorkers) {
            $process = new Process([
                PHP_BINARY,
                base_path('artisan'),
                'queue:work',
                '--queue=default',
                '--sleep=1',
                '--tries=1',
                '--timeout=300',
            ]);

            $process->disableOutput();
            $process->start();

            if ($process->getPid()) {
                $pids[] = $process->getPid();
            }
        }

        $filesystem->put($pidFile, json_encode($pids));
    }

    protected function isProcessRunning(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        return false;
    }
}
