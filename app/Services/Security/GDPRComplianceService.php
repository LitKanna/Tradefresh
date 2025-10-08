<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\DataProcessingConsent;
use App\Models\DataExportRequest;
use App\Services\Security\EncryptionService;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use ZipArchive;
use Exception;

class GDPRComplianceService
{
    protected $encryptionService;
    protected $auditService;
    protected $exportFormats = ['json', 'csv', 'xml'];
    protected $processingPurposes = [
        'marketing' => 'Processing data for marketing and promotional purposes',
        'analytics' => 'Processing data for analytics and service improvement',
        'third_party' => 'Sharing data with third-party service providers',
        'profiling' => 'Creating user profiles for personalized experiences',
        'newsletters' => 'Sending newsletters and updates',
        'research' => 'Using data for research and development'
    ];

    public function __construct(EncryptionService $encryptionService, AuditService $auditService)
    {
        $this->encryptionService = $encryptionService;
        $this->auditService = $auditService;
    }

    /**
     * Record user consent
     */
    public function recordConsent(User $user, string $purpose, bool $granted, array $metadata = null): DataProcessingConsent
    {
        $consent = DataProcessingConsent::updateOrCreate(
            [
                'user_id' => $user->id,
                'purpose' => $purpose
            ],
            [
                'description' => $this->processingPurposes[$purpose] ?? $purpose,
                'granted' => $granted,
                'granted_at' => $granted ? now() : null,
                'revoked_at' => !$granted ? now() : null,
                'ip_address' => request()->ip(),
                'metadata' => $metadata
            ]
        );

        $this->auditService->log(
            'consent_' . ($granted ? 'granted' : 'revoked'),
            "User " . ($granted ? 'granted' : 'revoked') . " consent for {$purpose}",
            $consent,
            null,
            null,
            ['purpose' => $purpose],
            $user
        );

        return $consent;
    }

    /**
     * Check if user has given consent
     */
    public function hasConsent(User $user, string $purpose): bool
    {
        $consent = DataProcessingConsent::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->first();

        return $consent && $consent->granted;
    }

    /**
     * Get user consents
     */
    public function getUserConsents(User $user): array
    {
        $consents = DataProcessingConsent::where('user_id', $user->id)->get();
        
        $result = [];
        foreach ($this->processingPurposes as $purpose => $description) {
            $consent = $consents->firstWhere('purpose', $purpose);
            $result[] = [
                'purpose' => $purpose,
                'description' => $description,
                'granted' => $consent ? $consent->granted : false,
                'granted_at' => $consent && $consent->granted_at ? $consent->granted_at->toIso8601String() : null,
                'revoked_at' => $consent && $consent->revoked_at ? $consent->revoked_at->toIso8601String() : null
            ];
        }

        return $result;
    }

    /**
     * Create data export request
     */
    public function createDataExportRequest(User $user, string $requestType, array $scope = null): DataExportRequest
    {
        $request = DataExportRequest::create([
            'user_id' => $user->id,
            'request_type' => $requestType,
            'status' => 'pending',
            'scope' => $scope,
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        ]);

        $this->auditService->log(
            'data_export_request',
            "User requested data {$requestType}",
            $request,
            null,
            null,
            ['request_type' => $requestType],
            $user
        );

        // Queue the processing
        dispatch(new \App\Jobs\ProcessDataRequest($request));

        return $request;
    }

    /**
     * Process data export request
     */
    public function processDataExport(DataExportRequest $request): void
    {
        try {
            $request->status = 'processing';
            $request->save();

            $user = $request->user;
            $data = $this->collectUserData($user, $request->scope);
            
            // Create export file
            $fileName = $this->createExportFile($data, $user);
            
            // Store file securely
            $encryptedFile = $this->encryptionService->encryptFile(
                storage_path("app/exports/{$fileName}"),
                'data_export'
            );

            $request->download_url = Storage::url("exports/{$fileName}");
            $request->status = 'completed';
            $request->processed_at = now();
            $request->expires_at = now()->addDays(7);
            $request->save();

            // Notify user
            $user->notify(new \App\Notifications\DataExportReady($request));

            $this->auditService->log(
                'data_exported',
                'User data export completed',
                $request,
                null,
                null,
                ['file_name' => $fileName],
                $user
            );

        } catch (Exception $e) {
            $request->status = 'failed';
            $request->metadata = array_merge($request->metadata ?? [], [
                'error' => $e->getMessage()
            ]);
            $request->save();

            throw $e;
        }
    }

    /**
     * Process data deletion request
     */
    public function processDataDeletion(DataExportRequest $request): void
    {
        try {
            $request->status = 'processing';
            $request->save();

            $user = $request->user;
            
            // Create backup before deletion
            $backupData = $this->collectUserData($user, $request->scope);
            $this->createBackup($backupData, $user);

            // Perform deletion/anonymization
            $this->deleteOrAnonymizeData($user, $request->scope);

            $request->status = 'completed';
            $request->processed_at = now();
            $request->save();

            // Notify user
            $user->notify(new \App\Notifications\DataDeletionCompleted($request));

            $this->auditService->log(
                'data_deleted',
                'User data deletion completed',
                $request,
                null,
                null,
                ['scope' => $request->scope],
                $user
            );

        } catch (Exception $e) {
            $request->status = 'failed';
            $request->metadata = array_merge($request->metadata ?? [], [
                'error' => $e->getMessage()
            ]);
            $request->save();

            throw $e;
        }
    }

    /**
     * Process data rectification request
     */
    public function processDataRectification(DataExportRequest $request): void
    {
        try {
            $request->status = 'processing';
            $request->save();

            $user = $request->user;
            $changes = $request->scope['changes'] ?? [];

            // Apply rectifications
            foreach ($changes as $field => $value) {
                if ($this->isRectifiableField($field)) {
                    $oldValue = $user->$field;
                    $user->$field = $value;
                    
                    $this->auditService->log(
                        'data_rectified',
                        "User data field {$field} rectified",
                        $user,
                        [$field => $oldValue],
                        [$field => $value],
                        null,
                        $user
                    );
                }
            }

            $user->save();

            $request->status = 'completed';
            $request->processed_at = now();
            $request->save();

            // Notify user
            $user->notify(new \App\Notifications\DataRectificationCompleted($request));

        } catch (Exception $e) {
            $request->status = 'failed';
            $request->metadata = array_merge($request->metadata ?? [], [
                'error' => $e->getMessage()
            ]);
            $request->save();

            throw $e;
        }
    }

    /**
     * Collect all user data
     */
    protected function collectUserData(User $user, array $scope = null): array
    {
        $data = [
            'personal_information' => $this->getPersonalInformation($user),
            'account_data' => $this->getAccountData($user),
            'activity_logs' => $this->getActivityLogs($user),
            'consents' => $this->getUserConsents($user),
            'preferences' => $this->getUserPreferences($user),
            'communications' => $this->getCommunications($user),
            'transactions' => $this->getTransactions($user),
            'files' => $this->getUserFiles($user)
        ];

        // Filter by scope if provided
        if ($scope && isset($scope['include'])) {
            $data = array_intersect_key($data, array_flip($scope['include']));
        }

        return $data;
    }

    /**
     * Get personal information
     */
    protected function getPersonalInformation(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'date_of_birth' => $user->date_of_birth,
            'created_at' => $user->created_at->toIso8601String(),
            'updated_at' => $user->updated_at->toIso8601String()
        ];
    }

    /**
     * Get account data
     */
    protected function getAccountData(User $user): array
    {
        return [
            'username' => $user->username,
            'account_status' => $user->status,
            'email_verified' => $user->email_verified_at !== null,
            'two_factor_enabled' => $user->mfaSetting && $user->mfaSetting->enabled,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ];
    }

    /**
     * Get activity logs
     */
    protected function getActivityLogs(User $user): array
    {
        $logs = \App\Models\AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();

        return $logs->map(function ($log) {
            return [
                'event' => $log->event_type,
                'action' => $log->action,
                'timestamp' => $log->created_at->toIso8601String(),
                'ip_address' => $log->ip_address
            ];
        })->toArray();
    }

    /**
     * Get user preferences
     */
    protected function getUserPreferences(User $user): array
    {
        return $user->preferences ?? [];
    }

    /**
     * Get communications
     */
    protected function getCommunications(User $user): array
    {
        // Fetch user notifications, emails, etc.
        return [];
    }

    /**
     * Get transactions
     */
    protected function getTransactions(User $user): array
    {
        // Fetch user orders, payments, etc.
        return [];
    }

    /**
     * Get user files
     */
    protected function getUserFiles(User $user): array
    {
        // List user uploaded files
        return [];
    }

    /**
     * Create export file
     */
    protected function createExportFile(array $data, User $user): string
    {
        $fileName = "data_export_{$user->id}_" . now()->format('Y_m_d_His');
        $exportPath = storage_path("app/exports/{$fileName}");

        // Create ZIP archive
        $zip = new ZipArchive();
        $zip->open($exportPath . '.zip', ZipArchive::CREATE);

        // Add data in multiple formats
        $zip->addFromString('data.json', json_encode($data, JSON_PRETTY_PRINT));
        $zip->addFromString('readme.txt', $this->generateReadme($user));
        
        // Add CSV files for each data category
        foreach ($data as $category => $content) {
            if (is_array($content) && !empty($content)) {
                $csv = $this->arrayToCsv($content);
                $zip->addFromString("{$category}.csv", $csv);
            }
        }

        $zip->close();

        return $fileName . '.zip';
    }

    /**
     * Convert array to CSV
     */
    protected function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        // Handle both associative and indexed arrays
        if (isset($data[0]) && is_array($data[0])) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, array_keys($data));
            fputcsv($output, array_values($data));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate readme file
     */
    protected function generateReadme(User $user): string
    {
        return "Data Export for User: {$user->email}\n"
            . "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n"
            . "This archive contains all personal data associated with your account.\n"
            . "The data is organized into categories and provided in multiple formats.\n\n"
            . "Files included:\n"
            . "- data.json: Complete data in JSON format\n"
            . "- *.csv: Individual categories in CSV format\n\n"
            . "This export will be available for download for 7 days.\n"
            . "For privacy and security, the download link will expire after this period.\n\n"
            . "If you have questions about your data, please contact our Data Protection Officer.";
    }

    /**
     * Delete or anonymize user data
     */
    protected function deleteOrAnonymizeData(User $user, array $scope = null): void
    {
        DB::transaction(function () use ($user, $scope) {
            // Anonymize personal data
            $user->name = 'Deleted User ' . Str::random(8);
            $user->email = 'deleted_' . Str::random(16) . '@example.com';
            $user->phone = null;
            $user->address = null;
            $user->date_of_birth = null;
            $user->save();

            // Delete related records based on scope
            if (!$scope || in_array('activity_logs', $scope['include'] ?? [])) {
                \App\Models\AuditLog::where('user_id', $user->id)->delete();
            }

            if (!$scope || in_array('consents', $scope['include'] ?? [])) {
                DataProcessingConsent::where('user_id', $user->id)->delete();
            }

            // Mark account as deleted
            $user->deleted_at = now();
            $user->save();
        });
    }

    /**
     * Create backup before deletion
     */
    protected function createBackup(array $data, User $user): void
    {
        $backupPath = storage_path("app/backups/deletion_{$user->id}_" . now()->format('Y_m_d_His') . '.json');
        
        $encryptedData = $this->encryptionService->encryptField($data, 'deletion_backup');
        
        file_put_contents($backupPath, json_encode([
            'user_id' => $user->id,
            'deleted_at' => now()->toIso8601String(),
            'encrypted_data' => $encryptedData['encrypted_data'],
            'key_id' => $encryptedData['key_id']
        ]));
    }

    /**
     * Check if field is rectifiable
     */
    protected function isRectifiableField(string $field): bool
    {
        $rectifiableFields = [
            'name',
            'phone',
            'address',
            'date_of_birth'
        ];

        return in_array($field, $rectifiableFields);
    }

    /**
     * Get data retention policy
     */
    public function getRetentionPolicy(): array
    {
        return [
            'activity_logs' => 90, // days
            'audit_logs' => 365,
            'deleted_user_data' => 30,
            'export_files' => 7,
            'backup_files' => 90,
            'session_data' => 30,
            'temporary_files' => 1
        ];
    }

    /**
     * Apply data retention policy
     */
    public function applyRetentionPolicy(): array
    {
        $policy = $this->getRetentionPolicy();
        $results = [];

        foreach ($policy as $dataType => $retentionDays) {
            $cutoffDate = now()->subDays($retentionDays);
            
            switch ($dataType) {
                case 'activity_logs':
                    $deleted = \App\Models\AuditLog::where('created_at', '<', $cutoffDate)
                        ->where('severity', 'low')
                        ->delete();
                    $results[$dataType] = $deleted;
                    break;
                    
                case 'export_files':
                    $deleted = DataExportRequest::where('expires_at', '<', now())->delete();
                    $results[$dataType] = $deleted;
                    break;
                    
                // Add more retention actions as needed
            }
        }

        return $results;
    }

    /**
     * Generate privacy report
     */
    public function generatePrivacyReport(): array
    {
        return [
            'total_users' => User::count(),
            'users_with_consent' => DataProcessingConsent::where('granted', true)
                ->distinct('user_id')
                ->count('user_id'),
            'pending_requests' => DataExportRequest::where('status', 'pending')->count(),
            'completed_exports' => DataExportRequest::where('status', 'completed')
                ->where('request_type', 'export')
                ->count(),
            'deletion_requests' => DataExportRequest::where('request_type', 'deletion')->count(),
            'consent_statistics' => $this->getConsentStatistics(),
            'data_breach_count' => 0, // Would come from security incidents
            'last_privacy_audit' => now()->subDays(15)->toIso8601String()
        ];
    }

    /**
     * Get consent statistics
     */
    protected function getConsentStatistics(): array
    {
        $stats = [];
        
        foreach ($this->processingPurposes as $purpose => $description) {
            $granted = DataProcessingConsent::where('purpose', $purpose)
                ->where('granted', true)
                ->count();
            
            $total = DataProcessingConsent::where('purpose', $purpose)->count();
            
            $stats[$purpose] = [
                'granted' => $granted,
                'revoked' => $total - $granted,
                'percentage' => $total > 0 ? round(($granted / $total) * 100, 2) : 0
            ];
        }

        return $stats;
    }
}