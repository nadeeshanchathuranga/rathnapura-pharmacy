<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class SyncSettingController extends Controller
{
    // Show sync settings page
    public function index()
    {
        $secondDb = [
            'host' => env('DB_HOST_SECOND'),
            'port' => env('DB_PORT_SECOND'),
            'database' => env('DB_DATABASE_SECOND'),
            'username' => env('DB_USERNAME_SECOND'),
            'password' => env('DB_PASSWORD_SECOND'),
        ];

        return Inertia::render('Settings/SyncSetting', [
            'secondDb' => $secondDb,
        ]);
    }

    // Update second DB credentials (NO REFRESH)
    public function updateSecondDb(Request $request)
    {
        // ✅ SPA-safe validation (NO redirect)
        $validator = Validator::make($request->all(), [
            'host' => 'required|string',
            'port' => 'required|string',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();

        try {
            // Update .env
            $envPath = base_path('.env');
            
            // Check if .env file is writable
            if (!is_writable($envPath)) {
                return response()->json([
                    'success' => false,
                    'message' => '.env file is not writable. Please run: chmod 664 ' . $envPath,
                ], 500);
            }
            
            $env = file_get_contents($envPath);

            $env = preg_replace('/DB_HOST_SECOND=.*/', 'DB_HOST_SECOND=' . $data['host'], $env);
            $env = preg_replace('/DB_PORT_SECOND=.*/', 'DB_PORT_SECOND=' . $data['port'], $env);
            $env = preg_replace('/DB_DATABASE_SECOND=.*/', 'DB_DATABASE_SECOND=' . $data['database'], $env);
            $env = preg_replace('/DB_USERNAME_SECOND=.*/', 'DB_USERNAME_SECOND=' . $data['username'], $env);
            $env = preg_replace('/DB_PASSWORD_SECOND=.*/', 'DB_PASSWORD_SECOND=' . ($data['password'] ?? ''), $env);

            $bytesWritten = file_put_contents($envPath, $env);
            
            if ($bytesWritten === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to write to .env file. Check file permissions.',
                ], 500);
            }

            // Ensure database exists
            $pdo = new \PDO(
                "mysql:host={$data['host']};port={$data['port']}",
                $data['username'],
                $data['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $pdo->exec(
                "CREATE DATABASE IF NOT EXISTS `{$data['database']}` 
                 CHARACTER SET utf8mb4 
                 COLLATE utf8mb4_general_ci;"
            );

            // Clear config cache
            \Artisan::call('config:clear');

            // Log activity
            $this->logActivity('save', 'sync setting', [
                'host' => $data['host'],
                'port' => $data['port'],
                'database' => $data['database'],
                'username' => $data['username'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Second DB saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Test second DB connection (NO REFRESH)
    public function testConnection(Request $request)
    {
        try {
            new \PDO(
                "mysql:host={$request->host};port={$request->port};dbname={$request->db}",
                $request->username,
                $request->password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_TIMEOUT => 3,
                ]
            );

            // Log activity
            $this->logActivity('test', 'sync setting', [
                'host' => $request->host,
                'port' => $request->port,
                'database' => $request->db,
                'username' => $request->username,
            ]);

            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Run migrations on second database
    public function migrateSecondDb(Request $request)
    {
        try {
            // Get second DB credentials from .env
            $host = env('DB_HOST_SECOND');
            $port = env('DB_PORT_SECOND');
            $database = env('DB_DATABASE_SECOND');
            $username = env('DB_USERNAME_SECOND');
            $password = env('DB_PASSWORD_SECOND');

            // Configure second database connection
            config([
                'database.connections.second_mysql' => [
                    'driver' => 'mysql',
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ]
            ]);

            // Run migrations on second database
            \Artisan::call('migrate', [
                '--database' => 'second_mysql',
                '--force' => true,
            ]);

            $migrationOutput = \Artisan::output();

            // Temporarily switch default database connection to second DB for seeding
            $originalConnection = config('database.default');
            config(['database.default' => 'second_mysql']);
            
            // Clear database connection cache
            \DB::purge('second_mysql');
            \DB::reconnect('second_mysql');

            // Run seeders on second database
            \Artisan::call('db:seed', [
                '--force' => true,
            ]);

            $seedOutput = \Artisan::output();

            // Restore original default connection
            config(['database.default' => $originalConnection]);
            \DB::reconnect($originalConnection);

            $fullOutput = "=== MIGRATIONS ===\n" . $migrationOutput . "\n=== SEEDING ===\n" . $seedOutput;

            // Log activity
            $this->logActivity('migrate', 'sync setting', [
                'status' => 'migrated',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Migrations and seeding completed successfully',
                'output' => $fullOutput,
            ]);
        } catch (\Exception $e) {
            \Log::error('Second DB Migration Failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getModuleMapping()
    {
        return [
            // Core Data
            'products' => ['products'],
            'brands' => ['brands'],
            'categories' => ['categories'],
            'types' => ['types'],
            'units' => ['measurement_units'],
            'purchase orders' => ['purchase_orders', 'purchase_order_products', 'purchase_order_requests', 'purchase_order_request_products'],
            'goods received' => ['goods_received_notes', 'goods_received_notes_products'],
            'goods received notes return' => ['goods_received_note_returns', 'goods_received_note_return_products'],
            'expenses' => ['purchase_expenses'],
            'suppliers' => ['suppliers'],
            'product transfer request' => ['product_transfer_requests', 'product_transfer_request_products'],
            'product release notes' => ['product_release_notes', 'product_release_note_produts'],
            'stock returns' => ['stock_transfer_returns', 'stock_transfer_return_products'],
            'customers' => ['customers'],
            'discounts' => ['discounts'],
            'taxes' => ['taxes'],
            'sales' => ['sales', 'sales_products'], // Assuming sales_products exists
            'product return' => ['sales_return', 'sales_return_products'],

            // Reports (Data already synced by above modules, but listed for verification/UI)
            'sales report' => [],
            'sales history' => [],
            'sync report' => [],
            'database backup' => [],
            'bill setting' => ['bill_settings'],
            'import & export' => [],
            'stock report' => [],
            'activity log' => ['activity_logs'],
            'expenses report' => [],
            'income report' => ['incomes'], // Incomes actually has a table
            'product release report' => [],
            'stock return report' => [],
            'low stock report' => [],
            'goods received notes report' => [],
            'goods received notes return report' => [],
            'product movement report' => ['product_movements'], // If table exists

            // System
            'users' => ['users', 'personal_access_tokens'],
            'company info' => ['company_information'],
            'app setting' => ['app_settings', 'smtp_settings'],
            'sync setting' => ['sync_settings', 'syn_logs'],

            // Other tables not covered above
            'other' => [
                'cache',
                'cache_locks',
                'currencies',
                'failed_jobs',
                'jobs',
                'job_batches',
                'migrations',
                'password_reset_tokens',
                'product_requests',
                'product_request_products',
                'sessions',
                'session_logs',
                'settings',
            ],
        ];
    }

    // Get list of Modules that need syncing (All Modules)
    public function getSyncList()
    {
        try {
            // Setup Secondary DB Connection
            $this->configureSecondDb();

            // 1. Get All Modules
            $mapping = $this->getModuleMapping();
            $modulesToSync = array_keys($mapping);

            return response()->json([
                'success' => true,
                'modules' => $modulesToSync
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare sync list: ' . $e->getMessage()
            ], 500);
        }
    }

    // Sync a specific Module
    public function syncModule(Request $request)
    {
        $request->validate([
            'module' => 'required|string'
        ]);

        $moduleName = $request->module;
        $mapping = $this->getModuleMapping();
        $tablesToSync = [];

        if (isset($mapping[$moduleName])) {
            $tablesToSync = $mapping[$moduleName];
        } else {
            return response()->json(['success' => false, 'message' => 'Unknown module'], 400);
        }

        try {
            $this->configureSecondDb();
            // Connection check
            \Illuminate\Support\Facades\DB::connection('mysql_second')->getPdo();

            // STEP 1: Detect changes BEFORE syncing
            $changedTables = $this->detectChangedTables($tablesToSync);

            // STEP 2: Perform the sync
            \Illuminate\Support\Facades\DB::connection('mysql_second')->statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($tablesToSync as $tableName) {
                $this->syncSingleTable($tableName);
            }

            \Illuminate\Support\Facades\DB::connection('mysql_second')->statement('SET FOREIGN_KEY_CHECKS=1;');

            // STEP 3: Log only the tables that were changed
            $this->logDetectedChanges($moduleName, $changedTables);

            return response()->json([
                'success' => true,
                'message' => "Synced $moduleName"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Sync $moduleName failed: " . $e->getMessage()
            ], 500);
        }
    }

    // --- Helpers ---

    private function configureSecondDb()
    {
        $config = [
            'driver' => 'mysql',
            'host' => env('DB_HOST_SECOND'),
            'port' => env('DB_PORT_SECOND'),
            'database' => env('DB_DATABASE_SECOND'),
            'username' => env('DB_USERNAME_SECOND'),
            'password' => env('DB_PASSWORD_SECOND'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ];
        \Illuminate\Support\Facades\Config::set('database.connections.mysql_second', $config);
    }

    private function getChecksums($connection)
    {
        // CHECKSUM TABLE table1, table2...
        // Need to construct one query for all tables or loop. One query is better but string limit?
        // Loop is safer.
        $cheks = [];
        $tables = \Illuminate\Support\Facades\DB::connection($connection)->select('SHOW TABLES');
        $dbName = ($connection == 'mysql') ? env('DB_DATABASE') : env('DB_DATABASE_SECOND');
        
        foreach ($tables as $t) {
            // handle object -> array -> value
            $arr = (array)$t;
            $tableName = reset($arr);
            
            // Skip views if possible, CHECKSUM might fail on views or return 0
            // We'll try-catch individual checksums
            try {
                $res = \Illuminate\Support\Facades\DB::connection($connection)->selectOne("CHECKSUM TABLE `$tableName`");
                $cheks[$tableName] = $res->Checksum;
            } catch (\Exception $e) {
                // If it fails (e.g. View), ignore or set null
                $cheks[$tableName] = null;
            }
        }
        return $cheks;
    }

    private function syncSingleTable($tableName)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable($tableName)) return;

        try {
            // Step 1: Check if table exists in second database, if not create it
            if (!\Illuminate\Support\Facades\Schema::connection('mysql_second')->hasTable($tableName)) {
                $this->createTableInSecondDb($tableName);
            }

            // Step 2: Get all rows from the primary table
            \Illuminate\Support\Facades\DB::table($tableName)->orderByRaw('1')->chunk(1000, function ($rows) use ($tableName) {
                foreach ($rows as $row) {
                    $rowArr = (array) $row;
                    // Try to use 'id' as the unique key if it exists, otherwise use the first column
                    $uniqueKey = array_key_exists('id', $rowArr) ? ['id' => $rowArr['id']] : [array_key_first($rowArr) => reset($rowArr)];
                    // Remove the unique key from the update data to avoid duplicate key error
                    $updateData = $rowArr;
                    unset($updateData[array_key_first($uniqueKey)]);
                    \Illuminate\Support\Facades\DB::connection('mysql_second')->table($tableName)->updateOrInsert(
                        $uniqueKey,
                        $updateData
                    );
                }
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function createTableInSecondDb($tableName)
    {
        try {
            // Get the CREATE TABLE statement from the primary database
            $primaryDb = env('DB_DATABASE');
            $createTableStatement = \Illuminate\Support\Facades\DB::selectOne(
                "SHOW CREATE TABLE `{$primaryDb}`.`{$tableName}`"
            );

            // The result has a property like 'Create Table'
            $createSql = $createTableStatement->{'Create Table'};

            // Execute the CREATE TABLE on the second database
            \Illuminate\Support\Facades\DB::connection('mysql_second')->statement($createSql);

        } catch (\Exception $e) {
            throw new \Exception("Failed to create table {$tableName} in second database: " . $e->getMessage());
        }
    }

    /**
     * Detect which tables have changes BEFORE syncing
     */
    private function detectChangedTables($tables)
    {
        $changedTables = [];
        
        foreach ($tables as $tableName) {
            // Skip if table doesn't exist in primary
            if (!\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
                continue;
            }
            
            try {
                // Get checksum from primary DB
                $primaryChecksum = \Illuminate\Support\Facades\DB::selectOne("CHECKSUM TABLE `$tableName`");
                $primarySum = $primaryChecksum->Checksum ?? 0;
                
                // Get checksum from secondary DB (if exists)
                $secondarySum = 0;
                try {
                    $secondaryChecksum = \Illuminate\Support\Facades\DB::connection('mysql_second')
                        ->selectOne("CHECKSUM TABLE `$tableName`");
                    $secondarySum = $secondaryChecksum->Checksum ?? 0;
                } catch (\Exception $e) {
                    // Table doesn't exist in secondary, treat as new
                    $secondarySum = 0;
                }
                
                // Only proceed if checksums differ
                if ($primarySum == $secondarySum) {
                    continue; // No change
                }
                
                // Get the most recent action from activity_logs for this table
                $recentAction = \Illuminate\Support\Facades\DB::table('activity_logs')
                    ->where('module', 'LIKE', '%' . rtrim($tableName, 's') . '%') // Match table to module (e.g., brands -> brand)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $action = 'update'; // Default action
                
                if ($recentAction) {
                    // Map activity_log actions to sync actions
                    $activityAction = strtolower($recentAction->action);
                    if (in_array($activityAction, ['create', 'add', 'insert', 'save'])) {
                        $action = 'add';
                    } elseif (in_array($activityAction, ['delete', 'remove', 'destroy'])) {
                        $action = 'delete';
                    } else {
                        $action = 'update';
                    }
                } elseif ($secondarySum == 0 && $primarySum > 0) {
                    // Table doesn't exist in secondary - new table
                    $action = 'add';
                }
                
                $changedTables[] = [
                    'table_name' => $tableName,
                    'action' => $action
                ];
                
            } catch (\Exception $e) {
                // If checksum fails, skip this table
                continue;
            }
        }
        
        return $changedTables;
    }

    /**
     * Log the detected changes to syn_logs table
     */
    private function logDetectedChanges($moduleName, $changedTables)
    {
        if (empty($changedTables)) {
            return; // No changes to log
        }

        $userId = \Illuminate\Support\Facades\Auth::id();
        $now = now();
        
        $logData = [];
        foreach ($changedTables as $change) {
            $logData[] = [
                'table_name' => $change['table_name'],
                'module' => ucfirst($moduleName), // Capitalize module name (e.g., "Brands")
                'action' => $change['action'],
                'synced_at' => $now,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        \Illuminate\Support\Facades\DB::table('syn_logs')->insert($logData);
    }

    /**
     * Log activity to activity_logs table
     */
    private function logActivity($action, $module, $details = [])
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'details' => json_encode($details),
        ]);
    }
}
