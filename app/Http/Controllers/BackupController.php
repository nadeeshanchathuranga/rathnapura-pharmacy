<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    /**
     * Create and download database backup
     */
    public function createBackup(Request $request)
    {
        try {
            $dbConnection = Config::get('database.default');
            $dbConfig = Config::get("database.connections.{$dbConnection}");
            
            if ($dbConnection !== 'mysql') {
                return response()->json(['error' => 'Only MySQL databases are supported'], 400);
            }

            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];
            $host = $dbConfig['host'];
            $port = $dbConfig['port'] ?? 3306;

            // Generate backup filename
            $backupName = 'backup_' . $database . '_' . date('Y_m_d_H_i_s') . '.sql';
            $backupPath = storage_path('app/backups');

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
                return response()->json(['error' => 'mysqldump command not found. Please ensure MySQL is installed and mysqldump is accessible.'], 500);
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

            if ($returnVar === 0 && file_exists($fullPath)) {
                // Return the backup file for download
                return Response::download($fullPath, $backupName)->deleteFileAfterSend(true);
            } else {
                return response()->json([
                    'error' => 'Database backup failed: ' . implode("\n", $output)
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Backup failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * List available backups
     */
    public function listBackups()
    {
        try {
            $backupPath = storage_path('app/backups');
            
            if (!file_exists($backupPath)) {
                return response()->json(['backups' => []]);
            }

            $files = scandir($backupPath);
            $backups = [];

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $fullPath = $backupPath . DIRECTORY_SEPARATOR . $file;
                    $backups[] = [
                        'name' => $file,
                        'size' => $this->formatBytes(filesize($fullPath)),
                        'created' => date('Y-m-d H:i:s', filemtime($fullPath))
                    ];
                }
            }

            return response()->json(['backups' => $backups]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to list backups: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download an existing backup
     */
    public function downloadBackup($filename)
    {
        try {
            $backupPath = storage_path('app/backups/' . $filename);
            
            if (!file_exists($backupPath) || pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
                return response()->json(['error' => 'Backup file not found'], 404);
            }

            return Response::download($backupPath, $filename);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Download failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Restore database from uploaded backup file
     */
    public function restoreBackup(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|max:102400' // Max 100MB
            ]);

            // Check file extension manually since SQL files don't have a standard MIME type
            $uploadedFile = $request->file('backup_file');
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            
            if ($extension !== 'sql') {
                return response()->json(['error' => 'Only .sql files are allowed.'], 400);
            }

            $dbConnection = Config::get('database.default');
            $dbConfig = Config::get("database.connections.{$dbConnection}");
            
            if ($dbConnection !== 'mysql') {
                return response()->json(['error' => 'Only MySQL databases are supported'], 400);
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
                return response()->json(['error' => 'mysql command not found. Please ensure MySQL is installed and mysql is accessible.'], 500);
            }

            // Save uploaded file temporarily
            $tempPath = storage_path('app/temp_restore_' . time() . '.sql');
            $uploadedFile->move(dirname($tempPath), basename($tempPath));

            // Build mysql restore command
            $command = sprintf(
                '"%s" --user=%s --password=%s --host=%s --port=%s %s < "%s"',
                $mysql,
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                $tempPath
            );

            // Execute the restore command
            $output = [];
            $returnVar = 0;
            exec($command . ' 2>&1', $output, $returnVar);

            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            if ($returnVar === 0) {
                return response()->json(['message' => 'Database restored successfully!']);
            } else {
                return response()->json([
                    'error' => 'Database restore failed: ' . implode("\n", $output)
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
