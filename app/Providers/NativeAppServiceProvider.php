<?php

namespace App\Providers;

use Native\Desktop\Facades\Window;
use Native\Desktop\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open([
            'title' => 'Garena Test Runner',
            'width' => 1280,
            'height' => 860,
            'minWidth' => 1100,
            'minHeight' => 720,
            'resizable' => true,
            'backgroundColor' => '#f1f5f9',
        ]);
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
