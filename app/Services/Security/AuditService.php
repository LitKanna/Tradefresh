<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class AuditService
{
    protected $enabledEvents = [
        'login',
        'logout',
        'failed_login',
        'password_changed',
        'mfa_enabled',
        'mfa_disabled',
        'role_assigned',
        'role_revoked',
        'permission_granted',
        'permission_revoked',
        'data_exported',
        'data_deleted',
        'security_incident',
        'access_denied',
        'file_accessed',
        'file_uploaded',
        'file_deleted',
        'settings_changed',
        'api_key_created',
        'api_key_revoked',
        'user_created',
        'user_updated',
        'user_deleted',
        'order_created',
        'order_updated',
        'payment_processed',
        'refund_issued'
    ];

    protected $severityLevels = [
        'login' => 'low',
        'logout' => 'low',
        'failed_login' => 'medium',
        'password_changed' => 'medium',
        'mfa_enabled' => 'low',
        'mfa_disabled' => 'medium',
        'role_assigned' => 'medium',
        'role_revoked' => 'medium',
        'permission_granted' => 'high',
        'permission_revoked' => 'high',
        'data_exported' => 'medium',
        'data_deleted' => 'high',
        'security_incident' => 'critical',
        'access_denied' => 'medium',
        'user_deleted' => 'high',
        'payment_processed' => 'medium',
        'refund_issued' => 'medium'
    ];

    /**
     * Log an audit event
     */
    public function log(
        string $eventType,
        string $action,
        Model $model = null,
        array $oldValues = null,
        array $newValues = null,
        array $metadata = null,
        User $user = null
    ): AuditLog {
        $user = $user ?? Auth::user();
        
        $log = AuditLog::create([
            'user_id' => $user ? $user->id : null,
            'event_type' => $eventType,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'action' => $action,
            'old_values' => $oldValues ? $this->sanitizeData($oldValues) : null,
            'new_values' => $newValues ? $this->sanitizeData($newValues) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
            'severity' => $this->severityLevels[$eventType] ?? 'low',
            'session_id' => session()->getId()
        ]);

        // Trigger real-time alerts for critical events
        if ($log->severity === 'critical') {
            $this->triggerCriticalEventAlert($log);
        }

        return $log;
    }

    /**
     * Log authentication event
     */
    public function logAuth(string $event, User $user = null, array $metadata = null): void
    {
        $this->log(
            $event,
            $this->getAuthActionDescription($event),
            null,
            null,
            null,
            array_merge($metadata ?? [], [
                'email' => $user ? $user->email : Request::input('email'),
                'timestamp' => now()->toIso8601String()
            ]),
            $user
        );
    }

    /**
     * Log model changes
     */
    public function logModelChange(Model $model, string $action): void
    {
        $eventType = strtolower(class_basename($model)) . '_' . $action;
        
        $oldValues = null;
        $newValues = null;

        if ($action === 'updated') {
            $oldValues = $model->getOriginal();
            $newValues = $model->getDirty();
        } elseif ($action === 'created') {
            $newValues = $model->toArray();
        } elseif ($action === 'deleted') {
            $oldValues = $model->toArray();
        }

        $this->log(
            $eventType,
            ucfirst($action) . ' ' . class_basename($model),
            $model,
            $oldValues,
            $newValues
        );
    }

    /**
     * Log access control event
     */
    public function logAccessControl(
        string $action,
        string $resource,
        bool $granted,
        User $targetUser = null,
        array $metadata = null
    ): void {
        $eventType = $granted ? $action : 'access_denied';
        
        $this->log(
            $eventType,
            $action . ' for ' . $resource,
            null,
            null,
            null,
            array_merge($metadata ?? [], [
                'resource' => $resource,
                'granted' => $granted,
                'target_user_id' => $targetUser ? $targetUser->id : null
            ])
        );
    }

    /**
     * Log file operation
     */
    public function logFileOperation(
        string $operation,
        string $fileName,
        string $filePath,
        array $metadata = null
    ): void {
        $this->log(
            'file_' . $operation,
            ucfirst($operation) . ' file: ' . $fileName,
            null,
            null,
            null,
            array_merge($metadata ?? [], [
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $metadata['size'] ?? null,
                'mime_type' => $metadata['mime_type'] ?? null
            ])
        );
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(
        string $type,
        string $description,
        string $severity = 'medium',
        array $metadata = null
    ): void {
        $this->log(
            'security_incident',
            $description,
            null,
            null,
            null,
            array_merge($metadata ?? [], [
                'incident_type' => $type,
                'severity' => $severity
            ])
        );
    }

    /**
     * Search audit logs
     */
    public function search(array $criteria): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = AuditLog::query();

        // Filter by user
        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        // Filter by event type
        if (isset($criteria['event_type'])) {
            if (is_array($criteria['event_type'])) {
                $query->whereIn('event_type', $criteria['event_type']);
            } else {
                $query->where('event_type', $criteria['event_type']);
            }
        }

        // Filter by model
        if (isset($criteria['model_type'])) {
            $query->where('model_type', $criteria['model_type']);
            if (isset($criteria['model_id'])) {
                $query->where('model_id', $criteria['model_id']);
            }
        }

        // Filter by severity
        if (isset($criteria['severity'])) {
            if (is_array($criteria['severity'])) {
                $query->whereIn('severity', $criteria['severity']);
            } else {
                $query->where('severity', $criteria['severity']);
            }
        }

        // Filter by date range
        if (isset($criteria['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($criteria['date_from']));
        }
        if (isset($criteria['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($criteria['date_to']));
        }

        // Filter by IP address
        if (isset($criteria['ip_address'])) {
            $query->where('ip_address', $criteria['ip_address']);
        }

        // Search in action or metadata
        if (isset($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('metadata', 'like', "%{$search}%");
            });
        }

        // Order and paginate
        $query->orderBy($criteria['order_by'] ?? 'created_at', $criteria['order_dir'] ?? 'desc');

        return $query->paginate($criteria['per_page'] ?? 50);
    }

    /**
     * Get audit trail for a specific model
     */
    public function getModelAuditTrail(Model $model): \Illuminate\Support\Collection
    {
        return AuditLog::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user activity timeline
     */
    public function getUserActivityTimeline(User $user, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $logs = AuditLog::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $timeline = [];
        foreach ($logs as $log) {
            $date = $log->created_at->format('Y-m-d');
            if (!isset($timeline[$date])) {
                $timeline[$date] = [];
            }
            $timeline[$date][] = [
                'time' => $log->created_at->format('H:i:s'),
                'event' => $log->event_type,
                'action' => $log->action,
                'severity' => $log->severity,
                'ip_address' => $log->ip_address
            ];
        }

        return $timeline;
    }

    /**
     * Get suspicious activities
     */
    public function getSuspiciousActivities(int $hours = 24): \Illuminate\Support\Collection
    {
        $startTime = now()->subHours($hours);
        
        return DB::table('audit_logs')
            ->select(
                'user_id',
                'ip_address',
                DB::raw('COUNT(*) as event_count'),
                DB::raw('COUNT(DISTINCT event_type) as unique_events'),
                DB::raw('SUM(CASE WHEN event_type = "failed_login" THEN 1 ELSE 0 END) as failed_logins'),
                DB::raw('SUM(CASE WHEN severity IN ("high", "critical") THEN 1 ELSE 0 END) as high_severity_events')
            )
            ->where('created_at', '>=', $startTime)
            ->groupBy('user_id', 'ip_address')
            ->having('failed_logins', '>', 3)
            ->orHaving('high_severity_events', '>', 5)
            ->orderBy('high_severity_events', 'desc')
            ->get();
    }

    /**
     * Get audit statistics
     */
    public function getStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $stats = [
            'total_events' => AuditLog::where('created_at', '>=', $startDate)->count(),
            'unique_users' => AuditLog::where('created_at', '>=', $startDate)
                ->distinct('user_id')->count('user_id'),
            'by_severity' => AuditLog::where('created_at', '>=', $startDate)
                ->groupBy('severity')
                ->selectRaw('severity, COUNT(*) as count')
                ->pluck('count', 'severity'),
            'by_event_type' => AuditLog::where('created_at', '>=', $startDate)
                ->groupBy('event_type')
                ->selectRaw('event_type, COUNT(*) as count')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'event_type'),
            'daily_trend' => $this->getDailyTrend($days)
        ];

        return $stats;
    }

    /**
     * Get daily trend
     */
    protected function getDailyTrend(int $days): array
    {
        $startDate = now()->subDays($days);
        
        $trend = DB::table('audit_logs')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $result = [];
        foreach ($trend as $day) {
            $result[$day->date] = $day->count;
        }

        return $result;
    }

    /**
     * Export audit logs
     */
    public function export(array $criteria, string $format = 'csv'): string
    {
        $logs = $this->search(array_merge($criteria, ['per_page' => 10000]))->items();
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($logs);
            case 'json':
                return $this->exportToJson($logs);
            case 'pdf':
                return $this->exportToPdf($logs);
            default:
                throw new Exception('Unsupported export format');
        }
    }

    /**
     * Export to CSV
     */
    protected function exportToCsv($logs): string
    {
        $csv = "Date,Time,User,Event Type,Action,Severity,IP Address,Model\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log->created_at->format('Y-m-d'),
                $log->created_at->format('H:i:s'),
                $log->user ? $log->user->email : 'System',
                $log->event_type,
                str_replace(',', ';', $log->action),
                $log->severity,
                $log->ip_address,
                $log->model_type ?? 'N/A'
            );
        }

        return $csv;
    }

    /**
     * Export to JSON
     */
    protected function exportToJson($logs): string
    {
        return json_encode($logs->map(function ($log) {
            return [
                'timestamp' => $log->created_at->toIso8601String(),
                'user' => $log->user ? $log->user->email : null,
                'event_type' => $log->event_type,
                'action' => $log->action,
                'severity' => $log->severity,
                'ip_address' => $log->ip_address,
                'model' => [
                    'type' => $log->model_type,
                    'id' => $log->model_id
                ],
                'changes' => [
                    'old' => $log->old_values,
                    'new' => $log->new_values
                ],
                'metadata' => $log->metadata
            ];
        }), JSON_PRETTY_PRINT);
    }

    /**
     * Sanitize sensitive data
     */
    protected function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'credit_card',
            'cvv',
            'ssn',
            'api_key',
            'api_secret',
            'token',
            'secret'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Get auth action description
     */
    protected function getAuthActionDescription(string $event): string
    {
        $descriptions = [
            'login' => 'User logged in',
            'logout' => 'User logged out',
            'failed_login' => 'Failed login attempt',
            'password_changed' => 'Password was changed',
            'mfa_enabled' => 'Multi-factor authentication enabled',
            'mfa_disabled' => 'Multi-factor authentication disabled'
        ];

        return $descriptions[$event] ?? $event;
    }

    /**
     * Trigger critical event alert
     */
    protected function triggerCriticalEventAlert(AuditLog $log): void
    {
        // Send notification to security team
        // This would integrate with your notification system
        \Log::critical('Critical security event detected', [
            'event_type' => $log->event_type,
            'action' => $log->action,
            'user_id' => $log->user_id,
            'ip_address' => $log->ip_address,
            'metadata' => $log->metadata
        ]);
    }

    /**
     * Cleanup old audit logs
     */
    public function cleanup(int $retentionDays = 90): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        // Archive critical logs before deletion
        $criticalLogs = AuditLog::where('created_at', '<', $cutoffDate)
            ->whereIn('severity', ['high', 'critical'])
            ->get();

        if ($criticalLogs->isNotEmpty()) {
            $this->archiveLogs($criticalLogs);
        }

        // Delete old logs
        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Archive logs
     */
    protected function archiveLogs($logs): void
    {
        // Store in long-term storage (S3, etc.)
        // Implementation depends on your infrastructure
        $archivePath = storage_path('archives/audit_logs_' . now()->format('Y_m_d') . '.json');
        file_put_contents($archivePath, $this->exportToJson($logs));
    }
}