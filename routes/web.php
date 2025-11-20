<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ProxyKeyController;
use App\Http\Controllers\Admin\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AccountController::class, 'index'])->name('admin.dashboard');
Route::get('/proxies', [ProxyKeyController::class, 'index'])->name('admin.proxies.index');
Route::get('/accounts', [AccountController::class, 'accountList'])->name('admin.accounts.list');
Route::post('/accounts', [AccountController::class, 'store'])->name('admin.accounts.store');
Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('admin.accounts.update');

Route::get('/garena', [TestController::class, 'garena'])->name('admin.garena.index');
Route::post('/garena/credentials', [TestController::class, 'saveGarenaCredentials'])->name('admin.garena.credentials');
Route::post('/garena/run', [TestController::class, 'runGarena'])->name('admin.garena.run');

Route::post('/accounts/import', [AccountController::class, 'import'])->name('admin.accounts.import');
Route::get('/accounts/export', [AccountController::class, 'export'])->name('admin.accounts.export');

Route::post('/proxies', [ProxyKeyController::class, 'store'])->name('admin.proxy.store');
Route::put('/proxies/{proxy}', [ProxyKeyController::class, 'update'])->name('admin.proxy.update');
Route::delete('/proxies/{proxy}', [ProxyKeyController::class, 'destroy'])->name('admin.proxy.destroy');
Route::post('/proxies/{proxy}/start', [ProxyKeyController::class, 'start'])->name('admin.proxy.start');
Route::post('/proxies/{proxy}/stop', [ProxyKeyController::class, 'stop'])->name('admin.proxy.stop');
Route::post('/proxies/{proxy}/test', [ProxyKeyController::class, 'test'])->name('admin.proxy.test');
Route::post('/proxies/{proxy}/rotate', [ProxyKeyController::class, 'rotate'])->name('admin.proxy.rotate');
