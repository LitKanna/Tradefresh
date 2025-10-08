<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Exception;

/**
 * ABR Credential Manager
 * Secure storage and management of ABR API credentials
 */
class ABRCredentialManager
{
    private const CREDENTIAL_FILE = 'abr/credentials.encrypted';
    private const BACKUP_FILE = 'abr/credentials.backup';
    private const ROTATION_HISTORY_FILE = 'abr/rotation-history.json';
    private const CACHE_KEY = 'abr_credentials';
    private const CACHE_TTL = 3600; // 1 hour
    
    /**
     * Store ABR credentials securely
     *
     * @param array $credentials
     * @return bool
     */
    public function storeCredentials(array $credentials): bool
    {
        try {
            // Validate credentials
            $this->validateCredentials($credentials);
            
            // Backup existing credentials
            $this->backupCredentials();
            
            // Add metadata
            $credentials['stored_at'] = now()->toIso8601String();
            $credentials['stored_by'] = auth()->user()->email ?? 'system';
            $credentials['environment'] = app()->environment();
            $credentials['checksum'] = $this->generateChecksum($credentials);
            
            // Encrypt and store
            $encrypted = Crypt::encryptString(json_encode($credentials));
            $path = storage_path('app/' . self::CREDENTIAL_FILE);
            
            // Ensure directory exists
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0700, true);
            }
            
            // Write with restricted permissions
            File::put($path, $encrypted);
            chmod($path, 0600);
            
            // Clear cache
            Cache::forget(self::CACHE_KEY);
            
            // Log the action
            $this->logCredentialAction('store', [
                'environment' => app()->environment(),
                'stored_by' => $credentials['stored_by'],
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to store ABR credentials', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Retrieve ABR credentials
     *
     * @return array|null
     */
    public function getCredentials(): ?array
    {
        try {
            // Check cache first
            if (Cache::has(self::CACHE_KEY)) {
                return Cache::get(self::CACHE_KEY);
            }
            
            // Read from file
            $path = storage_path('app/' . self::CREDENTIAL_FILE);
            
            if (!File::exists($path)) {
                return $this->getFromEnvironment();
            }
            
            $encrypted = File::get($path);
            $decrypted = Crypt::decryptString($encrypted);
            $credentials = json_decode($decrypted, true);
            
            // Verify checksum
            if (!$this->verifyChecksum($credentials)) {
                throw new Exception('Credential checksum verification failed');
            }
            
            // Cache for performance
            Cache::put(self::CACHE_KEY, $credentials, self::CACHE_TTL);
            
            return $credentials;
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve ABR credentials', [
                'error' => $e->getMessage(),
            ]);
            
            // Fall back to environment variables
            return $this->getFromEnvironment();
        }
    }
    
    /**
     * Get primary GUID
     *
     * @return string|null
     */
    public function getGuid(): ?string
    {
        $credentials = $this->getCredentials();
        return $credentials['guid'] ?? $credentials['primary_guid'] ?? null;
    }
    
    /**
     * Get backup GUID
     *
     * @return string|null
     */
    public function getBackupGuid(): ?string
    {
        $credentials = $this->getCredentials();
        return $credentials['backup_guid'] ?? null;
    }
    
    /**
     * Rotate credentials
     *
     * @param string $newGuid
     * @param string|null $reason
     * @return bool
     */
    public function rotateCredentials(string $newGuid, ?string $reason = null): bool
    {
        try {
            $current = $this->getCredentials();
            
            if (!$current) {
                return false;
            }
            
            // Record rotation history
            $this->recordRotation($current['guid'] ?? '', $newGuid, $reason);
            
            // Move current to backup
            if (isset($current['guid'])) {
                $current['backup_guid'] = $current['guid'];
            }
            
            // Set new primary
            $current['guid'] = $newGuid;
            $current['primary_guid'] = $newGuid;
            $current['rotated_at'] = now()->toIso8601String();
            $current['rotation_reason'] = $reason;
            
            // Store updated credentials
            return $this->storeCredentials($current);
            
        } catch (Exception $e) {
            Log::error('Failed to rotate ABR credentials', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Validate credential format
     *
     * @param array $credentials
     * @throws Exception
     */
    private function validateCredentials(array $credentials): void
    {
        if (!isset($credentials['guid']) && !isset($credentials['primary_guid'])) {
            throw new Exception('GUID is required');
        }
        
        $guid = $credentials['guid'] ?? $credentials['primary_guid'];
        
        if (!$this->isValidGuidFormat($guid)) {
            throw new Exception('Invalid GUID format');
        }
        
        // Validate backup GUID if provided
        if (isset($credentials['backup_guid']) && !$this->isValidGuidFormat($credentials['backup_guid'])) {
            throw new Exception('Invalid backup GUID format');
        }
    }
    
    /**
     * Check if GUID format is valid
     *
     * @param string $guid
     * @return bool
     */
    private function isValidGuidFormat(string $guid): bool
    {
        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $guid);
    }
    
    /**
     * Generate checksum for credentials
     *
     * @param array $credentials
     * @return string
     */
    private function generateChecksum(array $credentials): string
    {
        $data = $credentials['guid'] ?? $credentials['primary_guid'] ?? '';
        $data .= $credentials['backup_guid'] ?? '';
        $data .= $credentials['environment'] ?? '';
        
        return hash('sha256', $data . config('app.key'));
    }
    
    /**
     * Verify credential checksum
     *
     * @param array $credentials
     * @return bool
     */
    private function verifyChecksum(array $credentials): bool
    {
        if (!isset($credentials['checksum'])) {
            return true; // No checksum to verify (legacy)
        }
        
        $expected = $this->generateChecksum($credentials);
        return hash_equals($expected, $credentials['checksum']);
    }
    
    /**
     * Backup existing credentials
     *
     * @return bool
     */
    private function backupCredentials(): bool
    {
        try {
            $sourcePath = storage_path('app/' . self::CREDENTIAL_FILE);
            
            if (!File::exists($sourcePath)) {
                return true; // Nothing to backup
            }
            
            $backupDir = storage_path('app/abr/backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0700, true);
            }
            
            $backupPath = $backupDir . '/credentials-' . date('Y-m-d-His') . '.encrypted';
            
            return File::copy($sourcePath, $backupPath);
            
        } catch (Exception $e) {
            Log::warning('Failed to backup ABR credentials', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Get credentials from environment variables
     *
     * @return array|null
     */
    private function getFromEnvironment(): ?array
    {
        $guid = env('ABR_API_GUID');
        
        if (!$guid || $guid === 'YOUR_ABR_API_GUID_HERE') {
            return null;
        }
        
        return [
            'guid' => $guid,
            'primary_guid' => $guid,
            'backup_guid' => env('ABR_API_BACKUP_GUID'),
            'source' => 'environment',
            'loaded_at' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Record credential rotation
     *
     * @param string $oldGuid
     * @param string $newGuid
     * @param string|null $reason
     */
    private function recordRotation(string $oldGuid, string $newGuid, ?string $reason): void
    {
        try {
            $historyPath = storage_path('app/' . self::ROTATION_HISTORY_FILE);
            
            $history = [];
            if (File::exists($historyPath)) {
                $history = json_decode(File::get($historyPath), true) ?? [];
            }
            
            $history[] = [
                'old_guid' => substr($oldGuid, 0, 8) . '****',
                'new_guid' => substr($newGuid, 0, 8) . '****',
                'reason' => $reason,
                'rotated_at' => now()->toIso8601String(),
                'rotated_by' => auth()->user()->email ?? 'system',
            ];
            
            // Keep only last 100 entries
            $history = array_slice($history, -100);
            
            File::put($historyPath, json_encode($history, JSON_PRETTY_PRINT));
            
        } catch (Exception $e) {
            Log::warning('Failed to record credential rotation', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Log credential action
     *
     * @param string $action
     * @param array $context
     */
    private function logCredentialAction(string $action, array $context = []): void
    {
        Log::channel('abr')->info("ABR credential action: $action", array_merge($context, [
            'user' => auth()->user()->email ?? 'system',
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]));
    }
    
    /**
     * Get credential status
     *
     * @return array
     */
    public function getStatus(): array
    {
        $credentials = $this->getCredentials();
        
        if (!$credentials) {
            return [
                'configured' => false,
                'source' => 'none',
                'has_backup' => false,
            ];
        }
        
        return [
            'configured' => true,
            'source' => $credentials['source'] ?? 'encrypted_file',
            'has_backup' => !empty($credentials['backup_guid']),
            'stored_at' => $credentials['stored_at'] ?? null,
            'rotated_at' => $credentials['rotated_at'] ?? null,
            'environment' => $credentials['environment'] ?? app()->environment(),
        ];
    }
    
    /**
     * Export credentials (masked) for backup
     *
     * @return array
     */
    public function exportMasked(): array
    {
        $credentials = $this->getCredentials();
        
        if (!$credentials) {
            return [];
        }
        
        return [
            'primary_guid' => $this->maskGuid($credentials['guid'] ?? $credentials['primary_guid'] ?? ''),
            'backup_guid' => $this->maskGuid($credentials['backup_guid'] ?? ''),
            'configured' => true,
            'environment' => $credentials['environment'] ?? app()->environment(),
            'exported_at' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Mask GUID for display
     *
     * @param string $guid
     * @return string
     */
    private function maskGuid(string $guid): string
    {
        if (strlen($guid) < 20) {
            return $guid;
        }
        
        return substr($guid, 0, 8) . '-****-****-****-' . substr($guid, -12);
    }
    
    /**
     * Verify credentials are working
     *
     * @return bool
     */
    public function verifyCredentials(): bool
    {
        try {
            $guid = $this->getGuid();
            
            if (!$guid) {
                return false;
            }
            
            // Test with ABR API
            $testUrl = 'https://abr.business.gov.au/json/AbnDetails.aspx';
            $response = \Http::timeout(10)->get($testUrl, [
                'abn' => '51835430479', // Australian Government ABN
                'guid' => $guid,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return isset($data['Abn']);
            }
            
            return false;
            
        } catch (Exception $e) {
            Log::error('Failed to verify ABR credentials', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Get rotation history
     *
     * @param int $limit
     * @return array
     */
    public function getRotationHistory(int $limit = 10): array
    {
        try {
            $historyPath = storage_path('app/' . self::ROTATION_HISTORY_FILE);
            
            if (!File::exists($historyPath)) {
                return [];
            }
            
            $history = json_decode(File::get($historyPath), true) ?? [];
            
            return array_slice($history, -$limit);
            
        } catch (Exception $e) {
            Log::warning('Failed to get rotation history', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
}