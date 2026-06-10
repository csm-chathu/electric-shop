<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetTenantDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        $host   = $request->getHost();                          // e.g. shop1.lmucpos.lk
        $map    = config('tenants.domains', []);
        $dbName = $map[$host] ?? null;

        if ($dbName) {
            // Swap the database name on the active mysql connection.
            config(['database.connections.mysql.database' => $dbName]);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }
        // If domain is not in the map, the .env DB_DATABASE is used as-is.

        return $next($request);
    }
}
