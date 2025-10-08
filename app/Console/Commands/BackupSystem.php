<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class BackupSystem extends Command
{
    protected $signature = 'backup:run {--database} {--files}';

    protected $description = 'Backup database and files for Sydney Markets B2B';

    public function handle()
    {
        $this->info('=== Starting Sydney Markets Backup ===');

        $timestamp = Carbon::now()->format('Ymd-His');
        $backupDir = storage_path('app/backups');

        // Create backup directory
        if (! file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Backup database
        if ($this->option('database') !== false) {
            $this->backupDatabase($backupDir, $timestamp);
        }

        // Backup files
        if ($this->option('files') !== false) {
            $this->backupFiles($backupDir, $timestamp);
        }

        // If no options, backup everything
        if (! $this->option('database') && ! $this->option('files')) {
            $this->backupDatabase($backupDir, $timestamp);
            $this->backupFiles($backupDir, $timestamp);
        }

        // Clean old backups (keep last 30)
        $this->cleanOldBackups($backupDir);

        // Show statistics
        $this->showStatistics($backupDir);

        $this->info('=== Backup Complete ===');

        return Command::SUCCESS;
    }

    private function backupDatabase($backupDir, $timestamp)
    {
        $this->info('Backing up database...');

        $dbPath = database_path('database.sqlite');
        $backupPath = "$backupDir/db-$timestamp.sqlite";

        if (file_exists($dbPath)) {
            copy($dbPath, $backupPath);
            $this->info('✓ Database backed up to: '.basename($backupPath));
            $this->info('  Size: '.$this->formatBytes(filesize($backupPath)));

            // Log backup to file instead of database
            $logFile = "$backupDir/backup.log";
            $logEntry = sprintf(
                "[%s] Database backup: %s (%s)\n",
                now()->format('Y-m-d H:i:s'),
                basename($backupPath),
                $this->formatBytes(filesize($backupPath))
            );
            file_put_contents($logFile, $logEntry, FILE_APPEND);
        } else {
            $this->error('✗ Database file not found!');
        }
    }

    private function backupFiles($backupDir, $timestamp)
    {
        $this->info('Backing up critical files...');

        // Backup .env
        if (file_exists(base_path('.env'))) {
            copy(base_path('.env'), "$backupDir/.env-$timestamp");
            $this->info('✓ Environment file backed up');
        }

        // Create config backup directory
        $configBackupDir = "$backupDir/config-$timestamp";
        if (! file_exists($configBackupDir)) {
            mkdir($configBackupDir, 0755, true);
            mkdir("$configBackupDir/config", 0755, true);
            mkdir("$configBackupDir/routes", 0755, true);
        }

        // Copy config files
        $configFiles = glob(config_path('*.php'));
        foreach ($configFiles as $file) {
            copy($file, "$configBackupDir/config/".basename($file));
        }

        // Copy route files
        $routeFiles = glob(base_path('routes/*.php'));
        foreach ($routeFiles as $file) {
            copy($file, "$configBackupDir/routes/".basename($file));
        }

        $this->info('✓ Configuration files backed up to config-'.$timestamp);
    }

    private function cleanOldBackups($backupDir)
    {
        $this->info('Cleaning old backups...');

        $files = glob("$backupDir/db-*.sqlite");
        if (count($files) > 30) {
            // Sort by modification time
            usort($files, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Delete oldest files
            $toDelete = count($files) - 30;
            for ($i = 0; $i < $toDelete; $i++) {
                unlink($files[$i]);
            }

            $this->info("✓ Removed $toDelete old backup(s)");
        }
    }

    private function showStatistics($backupDir)
    {
        $dbBackups = glob("$backupDir/db-*.sqlite");
        $totalSize = 0;

        foreach ($dbBackups as $file) {
            $totalSize += filesize($file);
        }

        $this->info('');
        $this->info('Backup Statistics:');
        $this->info('Database backups: '.count($dbBackups));
        $this->info('Total size: '.$this->formatBytes($totalSize));
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision).' '.$units[$i];
    }
}
