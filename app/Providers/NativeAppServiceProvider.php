<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        $navigation = Menu::make(
            Menu::route('admin.dashboard', 'Dashboard'),
            Menu::route('admin.proxies.index', 'Proxy Keys'),
            Menu::route('admin.accounts.list', 'Accounts'),
            Menu::route('admin.garena.index', 'Account Test'),
            Menu::separator(),
        )->label('Điều hướng');

        Menu::create(
            Menu::app(),
            Menu::file(),
            Menu::edit(),
            Menu::view(),
            Menu::window(),
            $navigation,
        );

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
        return [];
    }
}
