<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckReverbStatus extends Command
{
    protected $signature = 'reverb:status {--auto-start : Automatically start Reverb if not running}';
    protected $description = 'Check if Reverb WebSocket server is running and optionally start it';

    public function handle()
    {
        $this->info('🔍 Checking Reverb WebSocket Server Status...');
        $this->info('============================================');

        $host = config('reverb.servers.reverb.host', '0.0.0.0');
        $port = config('reverb.servers.reverb.port', 8080);
        $hostname = config('reverb.servers.reverb.hostname', 'localhost');

        // Check if port is listening
        $isRunning = $this->checkPort($port);

        if ($isRunning) {
            $this->info('✅ Reverb is RUNNING!');
            $this->info('');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Status', '🟢 Active'],
                    ['Host', $host],
                    ['Port', $port],
                    ['Hostname', $hostname],
                    ['WebSocket URL', "ws://{$hostname}:{$port}"],
                    ['Admin URL', "http://{$hostname}:{$port}"],
                ]
            );

            // Try to get more info if available
            $this->checkWebSocketEndpoint($hostname, $port);

            return Command::SUCCESS;
        }

        $this->error('❌ Reverb is NOT RUNNING!');

        if ($this->option('auto-start')) {
            $this->info('');
            $this->info('🚀 Attempting to start Reverb...');

            // Start Reverb in background
            if (PHP_OS_FAMILY === 'Windows') {
                $command = 'start /B php artisan reverb:start --host=' . $host . ' --port=' . $port . ' --hostname=' . $hostname . ' > NUL 2>&1';
                pclose(popen($command, 'r'));
            } else {
                $command = 'nohup php artisan reverb:start --host=' . $host . ' --port=' . $port . ' --hostname=' . $hostname . ' > /dev/null 2>&1 &';
                exec($command);
            }

            // Wait for startup
            $this->info('Waiting for Reverb to start...');
            sleep(5);

            // Check again
            if ($this->checkPort($port)) {
                $this->info('');
                $this->info('✅ Reverb started successfully!');
                return Command::SUCCESS;
            } else {
                $this->error('Failed to start Reverb. Please start it manually:');
                $this->line('php artisan reverb:start');
                return Command::FAILURE;
            }
        } else {
            $this->info('');
            $this->warn('To start Reverb, run one of these commands:');
            $this->line('');
            $this->line('  1. Manual start (see output):');
            $this->line('     php artisan reverb:start');
            $this->line('');
            $this->line('  2. Background start (Windows):');
            $this->line('     start /B php artisan reverb:start');
            $this->line('');
            $this->line('  3. Auto-start with this command:');
            $this->line('     php artisan reverb:status --auto-start');
            $this->line('');
            $this->line('  4. Use the batch file:');
            $this->line('     start-reverb.bat');

            return Command::FAILURE;
        }
    }

    private function checkPort($port)
    {
        $connection = @fsockopen('localhost', $port, $errno, $errstr, 1);

        if ($connection) {
            fclose($connection);
            return true;
        }

        return false;
    }

    private function checkWebSocketEndpoint($hostname, $port)
    {
        try {
            // Try to connect to Reverb's HTTP endpoint
            $response = Http::timeout(2)->get("http://{$hostname}:{$port}/");

            if ($response->successful()) {
                $this->info('');
                $this->info('📡 WebSocket Server Details:');

                // Parse response if it contains useful info
                $body = $response->body();
                if (str_contains($body, 'reverb') || str_contains($body, 'laravel')) {
                    $this->line('Server Type: Laravel Reverb');
                }

                // Check app status
                $this->checkAppConnections();
            }
        } catch (\Exception $e) {
            // Silent fail - endpoint might not be accessible
        }
    }

    private function checkAppConnections()
    {
        // Check if any buyers/vendors are currently connected
        $this->info('');
        $this->info('📊 Connection Statistics:');

        // This would need actual implementation with Reverb's API
        // For now, show placeholder
        $this->table(
            ['Type', 'Count'],
            [
                ['Active Connections', 'N/A'],
                ['Active Channels', 'N/A'],
                ['Messages Today', 'N/A'],
            ]
        );

        $this->info('');
        $this->comment('Tip: Reverb dashboard available at http://localhost:8080/dashboard');
    }
}