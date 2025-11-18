<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập hệ thống</title>
    <style>
        :root {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f6fa;
            color: #1f2937;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .12);
            padding: 2.5rem;
        }

        h1 {
            margin: 0 0 1.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        label {
            font-size: .9rem;
            font-weight: 600;
            color: #374151;
        }

        input[type="email"],
        input[type="password"] {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: .85rem 1rem;
            font-size: 1rem;
            transition: .2s;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
            outline: none;
        }

        button {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .9rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        button:hover {
            background: #1d4ed8;
        }

        .muted {
            text-align: center;
            color: #6b7280;
            font-size: .9rem;
            margin-top: 1rem;
        }

        .alert {
            border-radius: 10px;
            padding: .85rem 1rem;
            margin-bottom: 1rem;
            font-size: .92rem;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .stack {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .logout {
            background: #f3f4f6;
            color: #111827;
        }

        .logout:hover {
            background: #e5e7eb;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .9rem;
            color: #374151;
        }

        @media (max-width:520px) {
            .card {
                padding: 2rem 1.5rem;
            }
        }

    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="card">
        @auth
        <h1>Xin chào, {{ auth()->user()->email }}</h1>
        <div class="alert success">Bạn đã đăng nhập thành công.</div>
        <p class="muted">Bạn có thể tiếp tục thao tác trong hệ thống quản lý đổi mật khẩu hoặc đăng xuất khi cần.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout">Đăng xuất</button>
        </form>
        @else
        <h1>Đăng nhập quản trị</h1>
        @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
        @endif
        @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
        @endif
        <form method="POST" action="{{ route('login.perform') }}">
            @csrf
            <div class="stack">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="stack">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
            </div>
            <label class="remember">
                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                Ghi nhớ đăng nhập
            </label>
            <button type="submit">Đăng nhập</button>
        </form>
        <p class="muted">Sử dụng tài khoản superadmin đã được cấp để truy cập hệ thống.</p>
        @endauth
    </div>
</body>
</html>
