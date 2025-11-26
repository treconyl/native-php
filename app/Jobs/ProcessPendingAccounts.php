<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\ProxyKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessPendingAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $proxyKeyId) {}

    public function handle(): void
    {
        $proxy = ProxyKey::find($this->proxyKeyId);

        if (! $proxy || ! $proxy->is_active) {
            return;
        }

        $account = null;

        DB::transaction(function () use (&$account) {
            $account = Account::where('status', 'pending')
                ->whereNotNull('current_password')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($account) {
                $account->update([
                    'status' => 'processing',
                    'last_attempted_at' => now(),
                    'last_error' => null,
                ]);
            }
        }, 3);

        if (! $account) {
            return;
        }

        $newPassword = $account->next_password ?: config('services.garena.default_new_password', 'Password#2025');

        RunGarenaTest::dispatch([
            'account_id' => $account->id,
            'username' => $account->login,
            'password' => $account->current_password,
            'new_password' => $newPassword,
            'proxy_key_id' => $proxy->id,
            'proxy_label' => $proxy->label,
            'multi_run' => true,
        ]);
    }
}
