<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AccountController::class, 'index'])->name('admin.dashboard');
Route::get('/proxies', [AccountController::class, 'proxiesIndex'])->name('admin.proxies.index');
Route::get('/accounts', [AccountController::class, 'accountList'])->name('admin.accounts.list');

Route::get('/garena', [TestController::class, 'garena'])->name('admin.garena.index');
Route::post('/garena/credentials', [TestController::class, 'saveGarenaCredentials'])->name('admin.garena.credentials');
Route::post('/garena/run', [TestController::class, 'runGarena'])->name('admin.garena.run');

Route::post('/accounts/import', [AccountController::class, 'import'])->name('admin.accounts.import');
Route::get('/accounts/export', [AccountController::class, 'export'])->name('admin.accounts.export');

Route::post('/proxies', [AccountController::class, 'storeProxyKey'])->name('admin.proxy.store');
Route::post('/proxies/{proxy}/start', [AccountController::class, 'startProxy'])->name('admin.proxy.start');
Route::post('/proxies/{proxy}/stop', [AccountController::class, 'stopProxy'])->name('admin.proxy.stop');
Route::post('/proxies/{proxy}/test', [AccountController::class, 'testProxy'])->name('admin.proxy.test');
Route::post('/proxies/{proxy}/rotate', [AccountController::class, 'rotateProxy'])->name('admin.proxy.rotate');
