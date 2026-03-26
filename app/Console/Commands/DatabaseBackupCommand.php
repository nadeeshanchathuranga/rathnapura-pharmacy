<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--name=} {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $dbConnection = Config::get('database.default');
            $dbConfig = Config::get("database.connections.{$dbConnection}");
            
            if ($dbConnection !== 'mysql') {
                $this->error('This command currently only supports MySQL databases.');
                return 1;
            }

            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];
            $host = $dbConfig['host'];
            $port = $dbConfig['port'] ?? 3306;

            // Generate backup filename
            $backupName = $this->option('name') ?: 'backup_' . $database . '_' . date('Y_m_d_H_i_s') . '.sql';
            $backupPath = $this->option('path') ?: storage_path('app/backups');

            // Create backups directory if it doesn't exist
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $fullPath = $backupPath . DIRECTORY_SEPARATOR . $backupName;

            // Try to find mysqldump in common locations
            $mysqldumpPaths = [
                'mysqldump', // In PATH
                'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
                'C:\Program Files\MySQL\MySQL Server 5.7\bin\mysqldump.exe',
                'C:\xampp\mysql\bin\mysqldump.exe',
                'C:\wamp64\bin\mysql\mysql8.0.31\bin\mysqldump.exe',
                'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe',
            ];

            $mysqldump = null;
            foreach ($mysqldumpPaths as $path) {
                if (file_exists($path) || $path === 'mysqldump') {
                    // Test if the command works
                    $testOutput = [];
                    $testReturn = 0;
                    exec("\"$path\" --version 2>&1", $testOutput, $testReturn);
                    if ($testReturn === 0) {
                        $mysqldump = $path;
                        break;
                    }
                }
            }

            if (!$mysqldump) {
                $this->error('mysqldump command not found. Please ensure MySQL is installed and mysqldump is accessible.');
                return 1;
            }

            // Build mysqldump command
            $command = sprintf(
                '"%s" --user=%s --password=%s --host=%s --port=%s --single-transaction --routines --triggers %s > "%s"',
                $mysqldump,
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                $fullPath
            );

            // Execute the backup command
            $output = [];
            $returnVar = 0;
            exec($command . ' 2>&1', $output, $returnVar);

            if ($returnVar === 0) {
                $this->info("Database backup created successfully: {$fullPath}");
                return 0;
            } else {
                $this->error('Database backup failed: ' . implode("\n", $output));
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return 1;
        }
    }
}
