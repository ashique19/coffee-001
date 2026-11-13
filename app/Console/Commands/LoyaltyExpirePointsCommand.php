<?php

namespace App\Console\Commands;

use App\Models\LoyaltyTransaction;
use App\Models\BusinessSetting;
use App\Support\Loyalty\LoyaltyConfig;
use Illuminate\Console\Command;

class LoyaltyExpirePointsCommand extends Command
{
    protected $signature = 'loyalty:expire-points';

    protected $description = 'Expire loyalty points past configured tenant expiry (ledger note only).';

    public function handle(): int
    {
        Tenant::query()->each(function (Tenant $tenant) {
            $days = LoyaltyConfig::for($tenant)->pointsExpiryDays();
            if ($days === null) {
                return;
            }

            $cutoff = now()->subDays($days);
            $customerIds = LoyaltyTransaction::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('occurred_at', '<', $cutoff)
                ->pluck('customer_id')
                ->unique();

            $this->line("Tenant {$tenant->slug}: {$customerIds->count()} customers with stale activity (review manually).");
        });

        return self::SUCCESS;
    }
}
