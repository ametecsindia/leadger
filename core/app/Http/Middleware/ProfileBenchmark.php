<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileBenchmark
{
    protected static $capturedQueries = [];
    protected static $listenerRegistered = false;

    public function handle(Request $request, Closure $next)
    {
        // A debugging tool: it rewrites storage/logs/profile_benchmark.json on EVERY request (each audio chunk, each
        // status check), a file that keeps growing. Only when PROFILE_BENCHMARK=true is set in .env.
        if (!filter_var(env('PROFILE_BENCHMARK', false), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        self::$capturedQueries = [];
        
        DB::listen(function ($query) {
            self::$capturedQueries[] = [
                'query' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'connection' => $query->connectionName,
            ];
        });

        $startTime = microtime(true);

        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $queries = self::$capturedQueries;
        $queryCount = count($queries);
        $queryTimeMs = round(array_sum(array_column($queries, 'time')), 2);

        // Sort slowest
        usort($queries, function ($a, $b) {
            return ($b['time'] <=> $a['time']);
        });
        $top5Slowest = array_slice($queries, 0, 5);

        $path = $request->path();
        $profileData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'path' => $path,
            'method' => $request->method(),
            'status' => $response->getStatusCode(),
            'server_time_ms' => $durationMs,
            'query_count' => $queryCount,
            'query_time_ms' => $queryTimeMs,
            'top_slowest_queries' => $top5Slowest,
        ];

        // Store in storage/logs/profile_benchmark.json
        $logFile = storage_path('logs/profile_benchmark.json');
        $existing = [];
        if (file_exists($logFile)) {
            $existing = json_decode(@file_get_contents($logFile), true) ?: [];
        }
        $existing[$path] = $profileData;
        @file_put_contents($logFile, json_encode($existing, JSON_PRETTY_PRINT));

        // Add headers for easy curl inspection
        $response->headers->set('X-Profile-Server-Time', $durationMs . 'ms');
        $response->headers->set('X-Profile-Queries', (string)$queryCount);
        $response->headers->set('X-Profile-Query-Time', $queryTimeMs . 'ms');

        return $response;
    }
}
