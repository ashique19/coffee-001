<?php

namespace App\Console\Commands;

use App\Models\BusinessSetting;
use App\Services\Tenancy\ClientTenantBootstrapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClientBootstrapCommand extends Command
{
    protected $signature = 'client:bootstrap {--slug= : Tenant slug (defaults to CLIENT_TENANT_SLUG)}';

    protected $description = 'Bootstrap a standalone client tenant (roles, branch, EGS, owner) from database/seed/client-bootstrap.json';

    public function handle(ClientTenantBootstrapService $bootstrap): int
    {
        $slug = (string) ($this->option('slug') ?: config('client_export.tenant_slug'));

        if ($slug === '') {
            $this->error('Pass --slug= or set CLIENT_TENANT_SLUG in .env');

            return self::FAILURE;
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();
        if ($tenant === null) {
            $this->error("Tenant not found: {$slug}. Run ClientExportedTenantSeeder first.");

            return self::FAILURE;
        }

        $path = database_path('seed/client-bootstrap.json');
        $options = [];
        if (File::exists($path)) {
            $options = json_decode(File::get($path), true) ?: [];
        }

        $bootstrap->bootstrap($tenant, $options);

        $this->info("Client bootstrap complete for [{$slug}].");
        $this->line('Staff ZATCA admin: /app/'.$slug.'/zatca (after login).');

        return self::SUCCESS;
    }
}
