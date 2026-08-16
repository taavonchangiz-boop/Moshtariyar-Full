<?php

namespace Modules\Core\Services;

use Modules\WooBridge\Entities\WooConnection;
use Modules\WooBridge\Entities\SyncLog;

class WooService
{
    public function getWooCommercePageData(): array
    {
        try {
            $connections = collect();
            $summary = ['connections' => 0, 'active' => 0, 'direct' => 0, 'failed_24h' => 0];

            if (class_exists(WooConnection::class)) {
                $connections = WooConnection::with(['syncLogs' => function($q) { $q->latest('id')->take(1); }])
                    ->orderByDesc('updated_at')
                    ->get();

                $summary['connections'] = $connections->count();
                $summary['active'] = $connections->where('is_active', true)->count();
                $summary['direct'] = $connections->filter(fn($c) => $c->hasApiCredentials())->count();

                try {
                    $summary['failed_24h'] = SyncLog::where('status', 'failed')
                        ->where('created_at', '>=', now()->subDay())
                        ->count();
                } catch (\Throwable $e) {}
            }

            return ['connections' => $connections, 'summary' => $summary];
        } catch (\Throwable $e) {
            return ['connections' => collect(), 'summary' => ['connections' => 0, 'active' => 0, 'direct' => 0, 'failed_24h' => 0]];
        }
    }

    public function getSyncLogsPageData(array $filters = []): array
    {
        try {
            $logs = collect();
            $summary = ['total' => 0, 'success' => 0, 'failed' => 0, 'pending' => 0];
            $connections = collect();

            if (class_exists(SyncLog::class)) {
                $query = SyncLog::with('connection')->latest('id');

                if (!empty($filters['connection_id'])) $query->where('connection_id', $filters['connection_id']);
                if (!empty($filters['status'])) $query->where('status', $filters['status']);
                if (!empty($filters['direction'])) $query->where('direction', $filters['direction']);
                if (!empty($filters['entity'])) $query->where('entity', $filters['entity']);

                $logs = $query->paginate(25);

                $summary['total'] = SyncLog::count();
                $summary['success'] = SyncLog::where('status', 'success')->count();
                $summary['failed'] = SyncLog::where('status', 'failed')->count();
                $summary['pending'] = SyncLog::where('status', 'pending')->count();
            }

            if (class_exists(WooConnection::class)) {
                $connections = WooConnection::where('is_active', true)->get();
            }

            return ['logs' => $logs, 'summary' => $summary, 'connections' => $connections];
        } catch (\Throwable $e) {
            return [
                'logs' => collect()->paginate(25),
                'summary' => ['total' => 0, 'success' => 0, 'failed' => 0, 'pending' => 0],
                'connections' => collect()
            ];
        }
    }
}
