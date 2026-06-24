<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantMigrate extends Command
{
    protected $signature   = 'tenant:migrate {tenant : Domain key from config/tenants.php} {--fresh} {--seed}';
    protected $description = 'Run migrations (optionally fresh + seed) for a specific tenant database';

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

        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
        $options = ['--force' => true];

        if ($this->option('fresh') && $this->option('seed')) {
            $options['--seed'] = true;
        }

        $this->call($command, $options);

        if ($this->option('seed') && ! $this->option('fresh')) {
            $this->call('db:seed', ['--force' => true]);
        }

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
