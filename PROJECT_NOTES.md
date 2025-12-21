# NativePHP Project Notes (quick scan)

## High-level purpose

- Laravel 12 app packaged with NativePHP Desktop for a local UI to manage Garena account password changes.
- Primary workflow: import Garena accounts, manage proxy keys, run Playwright automation to log in and rotate passwords, track status/logs.

## Main features and screens

- Dashboard: account stats, latest error, proxy counts, CSV import/export, quick proxy actions.
- Proxy Keys: CRUD, test/rotate/stop via proxyxoay.shop API, display current IP and expiry metadata.
- Accounts: list, filter, edit, bulk run for all active proxies, import/export CSV.
- Garena Test Runner: select account + new password + optional proxy, run Playwright job and view log tail.

## Core backend flow

- `routes/web.php` defines admin routes (dashboard, proxies, accounts, garena test).
- `app/Http/Controllers/Admin/AccountController.php`
  - Import: accepts CSV/TXT, supports `login|password` or CSV columns.
  - Export: streams CSV with status and last attempted info.
  - Dashboard stats: totals + breakdown for success/failed by day/week/month.
- `app/Http/Controllers/Admin/ProxyKeyController.php`
  - Start/test/rotate/stop uses `https://proxyxoay.shop/api/get.php`.
  - Stores proxy metadata (`last_proxy_http`, `last_proxy_rotated_at`, `last_proxy_expire_at`) in `meta`.
- `app/Http/Controllers/Admin/TestController.php`
  - Runs single Garena test via queued job.
  - Multi-run dispatches one worker per active proxy.

## Jobs and queue

- `app/Jobs/RunGarenaTest.php`
  - Spawns Node Playwright script `playwright/garena-runner.js`.
  - Updates `accounts` status (processing/success/failed) and logs to `storage/logs/garena-test.log`.
- `app/Jobs/ProcessPendingAccounts.php`
  - Rotates proxy, picks next pending account (transaction lock), generates password, and dispatches `RunGarenaTest`.
- Queue default is `database` (`config/queue.php`); jobs table exists via migrations.

## Playwright automation

- `playwright/garena-runner.js` handles:
  - Login flow + human-like typing/mouse/scrolling.
  - Navigates Account Center, runs password change, checks success/verification.
  - Uses env vars from queued job (username, password, new password, proxy id).

## Data model (migrations + models)

- `accounts`: login, current/next password, status, last errors, timestamps.
- `proxy_keys`: label, api_key, active flag, status, stop_requested, meta JSON.
- `garena_test_credentials`: last-used account + proxy + encrypted password fields.

## NativePHP Desktop integration

- `app/Providers/NativeAppServiceProvider.php`
  - Creates desktop menu linking to dashboard/proxy/accounts/test.
  - Opens a 1600x800 window titled "Garena Tool".
- `nativephp/electron/` is the Electron wrapper; `src/main/index.js` boots the bundled PHP app.

## Frontend stack

- Vite + Tailwind v4 (`resources/css/app.css`) with a custom "sunrise" UI theme.
- UIkit used for modals.
- Views in `resources/views/admin/*` and shared layout in `resources/views/layouts/admin.blade.php`.

## Local runtime assets

- `native_php` is a SQLite database file (likely used by NativePHP desktop runtime).
- Log file: `storage/logs/garena-test.log`.

## Common commands (from README + composer.json)

- Database reset: `php artisan native:db:wipe`, `php artisan migrate:fresh --seed`, `php artisan native:migrate`.
- Desktop app: `php artisan native:run`, build with `php artisan native:build`.
- Dev runner: `composer run dev` or desktop dev via `composer run native:dev`.
- Playwright script: `npm run playwright:garena`.
- Redis is expected (per README) for background processing.
