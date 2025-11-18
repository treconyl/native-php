<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunGarenaTest;
use App\Models\GarenaTestCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function garena()
    {
        $logPath = storage_path('logs/garena-test.log');
        $logLines = file_exists($logPath)
            ? array_slice(file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -200)
            : [];

        return view('admin.tests.garena', [
            'logLines' => $logLines,
            'credential' => GarenaTestCredential::first(),
            'activeNav' => 'tests',
            'title' => 'Garena Test Runner',
        ]);
    }

    public function runGarena(Request $request)
    {
        $credential = GarenaTestCredential::first();

        if (! $credential) {
            return back()->withErrors('Vui lòng nhập thông tin đăng nhập Garena trước khi chạy test.');
        }

        $logPath = storage_path('logs/garena-test.log');
        file_put_contents($logPath, '');

        $runner = new RunGarenaTest([
            'username' => $credential->username,
            'password' => $credential->password,
            'new_password' => $credential->new_password ?? 'Password#2025',
        ]);

        try {
            $runner->handle();
            Log::channel('garena_test')->info('[Garena Test] Đã chạy Dusk trực tiếp từ giao diện.');

            return back()->with('status', 'Đã chạy xong Garena Test, xem log bên dưới để theo dõi chi tiết.');
        } catch (\Throwable $e) {
            Log::channel('garena_test')->error('[Garena Test] Lỗi khi chạy trực tiếp', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Không thể chạy test Garena: ' . $e->getMessage());
        }
    }

    public function saveGarenaCredentials(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'new_password' => ['required', 'string'],
        ]);

        $credential = GarenaTestCredential::first();

        if ($credential) {
            $credential->update($data);
        } else {
            GarenaTestCredential::create($data);
        }

        Log::channel('garena_test')->info('[Garena Test] Đã cập nhật thông tin đăng nhập.');

        return back()->with('status', 'Đã lưu thông tin đăng nhập Garena.');
    }
}
