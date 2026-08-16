<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Entities\Setting;

class BackupController extends Controller
{
    public function index()
    {
        $tables = DB::select('SHOW TABLES');
        $tableNames = array_map(fn($row) => array_values((array) $row)[0], $tables);

        $topTables = [];
        $totalRows = 0;
        foreach ($tableNames as $tableName) {
            try {
                $rows = (int) DB::table($tableName)->count();
                $totalRows += $rows;
                $topTables[] = ['name' => $tableName, 'rows' => $rows];
            } catch (\Throwable $exception) {
                $topTables[] = ['name' => $tableName, 'rows' => 0];
            }
        }

        usort($topTables, fn ($first, $second) => $second['rows'] <=> $first['rows']);
        $topTables = array_slice($topTables, 0, 8);

        $stats = [
            'database' => DB::getDatabaseName(),
            'total_rows' => $totalRows,
            'settings_count' => Schema::hasTable('settings') ? Setting::where('is_secret', false)->count() : 0,
            'top_tables' => $topTables,
        ];

        return view('app.backups', compact('tableNames', 'stats'));
    }

    public function database()
    {
        $db = DB::getDatabaseName();
        $filename = 'moshtariyar-db-backup-' . now()->format('Ymd-His') . '.sql';

        return response()->streamDownload(function () use ($db) {
            echo "-- MoshtariYar CRM Database Backup\n";
            echo "-- Database: {$db}\n";
            echo "-- Generated at: " . now()->toDateTimeString() . "\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $row) {
                $table = array_values((array) $row)[0];
                $create = DB::select("SHOW CREATE TABLE `{$table}`");
                $createSql = array_values((array) $create[0])[1] ?? '';
                echo "DROP TABLE IF EXISTS `{$table}`;\n";
                echo $createSql . ";\n\n";

                DB::table($table)->orderByRaw('1')->chunk(500, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        $data = (array) $row;
                        $columns = array_map(fn ($column) => '`' . str_replace('`', '``', $column) . '`', array_keys($data));
                        $values = array_map(function ($value) {
                            if ($value === null) {
                                return 'NULL';
                            }

                            return DB::getPdo()->quote((string) $value);
                        }, array_values($data));

                        echo "INSERT INTO `{$table}` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ");\n";
                    }
                });
                echo "\n";
            }
            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, ['Content-Type' => 'application/sql; charset=UTF-8']);
    }

    public function settings()
    {
        $rows = Schema::hasTable('settings') ? Setting::where('is_secret', false)->get(['group', 'key', 'value']) : collect();

        return response()->json([
            'app' => 'مشتری‌یار',
            'exported_at' => now()->toIso8601String(),
            'settings' => $rows,
        ], 200, [
            'Content-Disposition' => 'attachment; filename="moshtariyar-settings-' . now()->format('Ymd-His') . '.json"',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}