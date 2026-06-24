<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantSeed extends Command
{
    protected $signature   = 'tenant:seed {tenant : Domain key from config/tenants.php} {--class=DatabaseSeeder}';
    protected $description = 'Run database seeders for a specific tenant database';

    public function handle(): int
    {
        $key     = $this->argument('tenant');
        $tenants = config('tenants', []);

        if (! isset($tenants[$key])) {
            $this->error("Tenant '{$key}' not found in config/tenants.php");
            $this->line('Available tenants: ' . implode(', ', array_keys($tenants)));
            return 1;
        }

        $tenant = $tenants[$key];
        $this->applyConnection($tenant);

        $this->info("Tenant: {$key}");
        $this->info("Database: {$tenant['database']}");

        $this->call('db:seed', [
            '--class' => $this->option('class'),
            '--force' => true,
        ]);

        return 0;
    }

    private function applyConnection(array $tenant): void
    {
        config([
            'database.connections.mysql.database' => $tenant['database'],
            'database.connections.mysql.username' => $tenant['username'],
            'database.connections.mysql.password' => $tenant['password'],
            'database.connections.mysql.host'     => $tenant['host'] ?? config('database.connections.mysql.host'),
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');
    }
}
