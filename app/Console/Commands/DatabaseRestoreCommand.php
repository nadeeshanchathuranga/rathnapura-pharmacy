<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class DatabaseRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore {file : Path to the backup SQL file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore database from a backup SQL file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $filePath = $this->argument('file');
            
            if (!file_exists($filePath)) {
                $this->error("Backup file not found: {$filePath}");
                return 1;
            }

            if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'sql') {
                $this->error('File must be a .sql file');
                return 1;
            }

            $dbConnection = Config::get('database.default');
            $dbConfig = Config::get("database.connections.{$dbConnection}");
            
            if ($dbConnection !== 'mysql') {
                $this->error('This command currently only supports MySQL databases.');
                return 1;
            }

            // Confirm before proceeding
            if (!$this->confirm('This will replace all data in your database. Are you sure you want to continue?')) {
                $this->info('Restore cancelled.');
                return 0;
            }

            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];
            $host = $dbConfig['host'];
            $port = $dbConfig['port'] ?? 3306;

            // Try to find mysql command in common locations
            $mysqlPaths = [
                'mysql', // In PATH
                'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe',
                'C:\Program Files\MySQL\MySQL Server 5.7\bin\mysql.exe',
                'C:\xampp\mysql\bin\mysql.exe',
                'C:\wamp64\bin\mysql\mysql8.0.31\bin\mysql.exe',
                'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe',
            ];

            $mysql = null;
            foreach ($mysqlPaths as $path) {
                if (file_exists($path) || $path === 'mysql') {
                    // Test if the command works
                    $testOutput = [];
                    $testReturn = 0;
                    exec("\"$path\" --version 2>&1", $testOutput, $testReturn);
                    if ($testReturn === 0) {
                        $mysql = $path;
                        break;
                    }
                }
            }

            if (!$mysql) {
                $this->error('mysql command not found. Please ensure MySQL is installed and mysql is accessible.');
                return 1;
            }

            $this->info('Starting database restore...');

            // Build mysql restore command
            $command = sprintf(
                '"%s" --user=%s --password=%s --host=%s --port=%s %s < "%s"',
                $mysql,
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                $filePath
            );

            // Execute the restore command
            $output = [];
            $returnVar = 0;
            exec($command . ' 2>&1', $output, $returnVar);

            if ($returnVar === 0) {
                $this->info("Database restored successfully from: {$filePath}");
                return 0;
            } else {
                $this->error('Database restore failed: ' . implode("\n", $output));
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('Restore failed: ' . $e->getMessage());
            return 1;
        }
    }
}
