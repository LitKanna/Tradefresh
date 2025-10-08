<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EnhancedAuditService
{
    /**
     * Critical events that require immediate attention
     */
    protected $criticalEvents = [
        'unauthorized_access_attempt',
        'privilege_escalation',
        'data_breach_attempt',
        'payment_fraud_attempt',
        'admin_account_compromise',
        'mass_data_export',
        'system_configuration_change',
        'security_policy_violation'
    ];

    /**
     * Security events classification
     */
    protected $eventClassification = [
        'authentication' => [
            'login_success', 'login_failed', 'logout', 'password_reset', 
            'two_factor_enabled', 'two_factor_disabled', 'account_locked'
        ],
        'authorization' => [
            'permission_granted', 'permission_denied', 'role_changed', 
            'privilege_escalation', 'unauthorized_access_attempt'
        ],
        'data_access' => [
            'data_viewed', 'data_exported', 'sensitive_data_accessed',
            'mass_data_access', 'unauthorized_data_access'
        ],
        'data_modification' => [
            'data_created', 'data_updated', 'data_deleted', 'bulk_operation',
            'sensitive_data_modified', 'data_breach_attempt'
        ],
        'system_security' => [
            'security_scan', 'vulnerability_detected', 'malware_detected',
            'suspicious_activity', 'rate_limit_exceeded', 'ip_blocked'
        ],
        'compliance' => [
            'gdpr_request', 'data_retention_policy', 'audit_trail_accessed',
            'compliance_violation', 'policy_enforcement'
        ]
    ];

    /**
     * Log comprehensive audit event
     */
    public function logEvent($event, $auditable = null, array $options = [])
    {
        $user = Auth::user();
        $correlationId = Str::uuid();

        $auditData = [
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable ? $auditable->id : null,
            'user_type' => $user ? get_class($user) : 'system',
            'user_id' => $user ? $user->id : null,
            'event' => $event,
            'audit_type' => $this->determineAuditType($event),
            'old_values' => $options['old_values'] ?? null,
            'new_values' => $options['new_values'] ?? null,
            'changed_fields' => $options['changed_fields'] ?? null,
            'url' => request()->fullUrl(),
            'ip_address' => $this->getClientIP(),
            'user_agent' => request()->userAgent(),
            'method' => request()->method(),
            'request_data' => $this->sanitizeRequestData(request()->all()),
            'response_data' => $options['response_data'] ?? null,
            'response_code' => $options['response_code'] ?? null,
            'session_id' => session()->getId(),
            'correlation_id' => $correlationId,
            'tags' => $this->generateTags($event, $options),
            'notes' => $options['notes'] ?? null,
            'metadata' => $this->enrichMetadata($options['metadata'] ?? []),
            'environment' => app()->environment(),
            'risk_level' => $this->assessRiskLevel($event, $options),
            'created_at' => now()
        ];

        // Store audit log
        $auditLog = AuditLog::create($auditData);

        // Handle critical events
        if ($this->isCriticalEvent($event)) {
            $this->handleCriticalEvent($auditLog);
        }

        // Trigger real-time monitoring if needed
        if ($this->requiresRealTimeMonitoring($event)) {
            $this->triggerRealTimeAlert($auditLog);
        }

        return $auditLog;
    }

    /**
     * Log security incident with enhanced context
     */
    public function logSecurityIncident($incident, array $context = [])
    {
        $incidentId = Str::uuid();
        
        $this->logEvent('security_incident', null, [
            'notes' => "Security incident: {$incident}",
            'metadata' => array_merge($context, [
                'incident_id' => $incidentId,
                'detection_timestamp' => now()->timestamp,
                'severity' => $context['severity'] ?? 'medium',
                'automated_detection' => $context['automated'] ?? true,
                'threat_indicators' => $context['indicators'] ?? [],
                'affected_resources' => $context['resources'] ?? [],
                'response_actions' => $context['actions'] ?? []
            ]),
            'response_code' => 200
        ]);

        return $incidentId;
    }

    /**
     * Log data access with privacy compliance
     */
    public function logDataAccess($dataType, $operation, $records = null, array $context = [])
    {
        $this->logEvent('data_access', null, [
            'notes' => "Data access: {$operation} on {$dataType}",
            'metadata' => array_merge($context, [
                'data_type' => $dataType,
                'operation' => $operation,
                'record_count' => is_countable($records) ? count($records) : ($records ? 1 : 0),
                'record_ids' => $this->sanitizeRecordIds($records),
                'access_method' => request()->is('api/*') ? 'api' : 'web',
                'data_classification' => $this->classifyData($dataType),
                'legal_basis' => $context['legal_basis'] ?? 'legitimate_interest',
                'retention_period' => $context['retention'] ?? '7_years'
            ]),
            'response_code' => 200
        ]);
    }

    /**
     * Log payment-related activities for PCI compliance
     */
    public function logPaymentActivity($activity, array $paymentData = [])
    {
        $sanitizedData = $this->sanitizePaymentData($paymentData);
        
        $this->logEvent('payment_activity', null, [
            'notes' => "Payment activity: {$activity}",
            'metadata' => [
                'activity' => $activity,
                'payment_method' => $sanitizedData['method'] ?? 'unknown',
                'amount' => $sanitizedData['amount'] ?? null,
                'currency' => $sanitizedData['currency'] ?? 'AUD',
                'merchant_id' => $sanitizedData['merchant_id'] ?? null,
                'transaction_id' => $sanitizedData['transaction_id'] ?? null,
                'card_last_four' => $sanitizedData['card_last_four'] ?? null,
                'pci_compliance_level' => 'level_1',
                'encryption_status' => 'encrypted',
                'tokenization_used' => $sanitizedData['tokenized'] ?? true
            ],
            'response_code' => 200
        ]);
    }

    /**
     * Get audit trail for compliance reporting
     */
    public function getAuditTrail($startDate, $endDate, array $filters = [])
    {
        $query = AuditLog::whereBetween('created_at', [$startDate, $endDate]);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['event_type'])) {
            $query->where('event', $filters['event_type']);
        }

        if (isset($filters['audit_type'])) {
            $query->where('audit_type', $filters['audit_type']);
        }

        if (isset($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        if (isset($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($filters['per_page'] ?? 100);
    }

    /**
     * Generate compliance report
     */
    public function generateComplianceReport($type, $startDate, $endDate)
    {
        $reportData = [
            'report_id' => Str::uuid(),
            'type' => $type,
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'generated_at' => now(),
            'generated_by' => Auth::user() ? Auth::user()->id : 'system'
        ];

        switch ($type) {
            case 'gdpr':
                $reportData['data'] = $this->generateGdprReport($startDate, $endDate);
                break;
            case 'pci':
                $reportData['data'] = $this->generatePciReport($startDate, $endDate);
                break;
            case 'security':
                $reportData['data'] = $this->generateSecurityReport($startDate, $endDate);
                break;
            case 'access':
                $reportData['data'] = $this->generateAccessReport($startDate, $endDate);
                break;
        }

        // Log report generation
        $this->logEvent('compliance_report_generated', null, [
            'notes' => "Compliance report generated: {$type}",
            'metadata' => [
                'report_id' => $reportData['report_id'],
                'report_type' => $type,
                'period_days' => Carbon::parse($endDate)->diffInDays($startDate)
            ]
        ]);

        return $reportData;
    }

    /**
     * Determine audit type from event
     */
    protected function determineAuditType($event)
    {
        foreach ($this->eventClassification as $type => $events) {
            if (in_array($event, $events)) {
                return $type;
            }
        }

        return 'general';
    }

    /**
     * Assess risk level of event
     */
    protected function assessRiskLevel($event, $options)
    {
        // Critical events
        if (in_array($event, $this->criticalEvents)) {
            return 'critical';
        }

        // High risk indicators
        $highRiskIndicators = [
            'failed_login_attempt',
            'permission_denied',
            'suspicious_activity',
            'data_export',
            'admin_action'
        ];

        if (in_array($event, $highRiskIndicators)) {
            return 'high';
        }

        // Medium risk indicators
        $mediumRiskIndicators = [
            'data_modification',
            'file_upload',
            'password_change',
            'profile_update'
        ];

        if (in_array($event, $mediumRiskIndicators)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Generate relevant tags for the event
     */
    protected function generateTags($event, $options)
    {
        $tags = ['audit'];

        // Add event classification tags
        foreach ($this->eventClassification as $type => $events) {
            if (in_array($event, $events)) {
                $tags[] = $type;
                break;
            }
        }

        // Add risk level tag
        $riskLevel = $this->assessRiskLevel($event, $options);
        $tags[] = "risk_{$riskLevel}";

        // Add user type tag
        if (Auth::check()) {
            $tags[] = 'authenticated';
            $tags[] = strtolower(class_basename(get_class(Auth::user())));
        } else {
            $tags[] = 'anonymous';
        }

        // Add request context tags
        if (request()->is('api/*')) {
            $tags[] = 'api';
        }

        if (request()->isSecure()) {
            $tags[] = 'https';
        }

        // Add custom tags from options
        if (isset($options['tags'])) {
            $tags = array_merge($tags, (array) $options['tags']);
        }

        return array_unique($tags);
    }

    /**
     * Enrich metadata with additional context
     */
    protected function enrichMetadata($metadata)
    {
        return array_merge($metadata, [
            'timestamp' => now()->timestamp,
            'timezone' => config('app.timezone'),
            'app_version' => config('app.version', '1.0.0'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'request_id' => request()->header('X-Request-ID', Str::uuid()),
            'forwarded_for' => request()->header('X-Forwarded-For'),
            'real_ip' => request()->header('X-Real-IP'),
            'country' => $this->getCountryFromIP(),
            'device_type' => $this->detectDeviceType(),
            'browser_info' => $this->getBrowserInfo()
        ]);
    }

    /**
     * Get real client IP address
     */
    protected function getClientIP()
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }

        return request()->ip();
    }

    /**
     * Sanitize request data for logging
     */
    protected function sanitizeRequestData($data)
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password', 'new_password',
            'token', 'api_key', 'secret', 'credit_card', 'card_number', 'cvv', 'ssn'
        ];

        return $this->recursiveSanitize($data, $sensitiveFields);
    }

    /**
     * Recursively sanitize sensitive data
     */
    protected function recursiveSanitize($data, $sensitiveFields)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->recursiveSanitize($value, $sensitiveFields);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Check if event is critical
     */
    protected function isCriticalEvent($event)
    {
        return in_array($event, $this->criticalEvents);
    }

    /**
     * Handle critical security events
     */
    protected function handleCriticalEvent($auditLog)
    {
        // Send immediate alerts
        // This would typically integrate with monitoring systems
        \Log::critical('Critical security event detected', [
            'audit_log_id' => $auditLog->id,
            'event' => $auditLog->event,
            'user_id' => $auditLog->user_id,
            'ip_address' => $auditLog->ip_address,
            'correlation_id' => $auditLog->correlation_id
        ]);

        // Additional automated responses could be triggered here
    }

    /**
     * Check if event requires real-time monitoring
     */
    protected function requiresRealTimeMonitoring($event)
    {
        $realTimeEvents = [
            'login_failed', 'suspicious_activity', 'rate_limit_exceeded',
            'unauthorized_access_attempt', 'payment_fraud_attempt'
        ];

        return in_array($event, $realTimeEvents);
    }

    /**
     * Trigger real-time security alert
     */
    protected function triggerRealTimeAlert($auditLog)
    {
        // Implementation would depend on monitoring system
        // Could be webhook, message queue, etc.
    }

    /**
     * Additional helper methods for metadata enrichment
     */
    protected function getCountryFromIP()
    {
        // This would typically use a GeoIP service
        return 'AU'; // Default to Australia for Sydney Markets
    }

    protected function detectDeviceType()
    {
        $userAgent = request()->userAgent();
        if (preg_match('/mobile|android|iphone/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        return 'desktop';
    }

    protected function getBrowserInfo()
    {
        $userAgent = request()->userAgent();
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            return ['name' => 'Chrome', 'version' => $matches[1]];
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            return ['name' => 'Firefox', 'version' => $matches[1]];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
            return ['name' => 'Safari', 'version' => $matches[1]];
        }
        return ['name' => 'Unknown', 'version' => '0.0'];
    }

    protected function sanitizeRecordIds($records)
    {
        if (is_null($records)) return null;
        if (!is_countable($records)) return [$records];
        return array_slice($records, 0, 100); // Limit to first 100 IDs
    }

    protected function classifyData($dataType)
    {
        $sensitiveTypes = ['user', 'buyer', 'vendor', 'payment', 'order'];
        return in_array(strtolower($dataType), $sensitiveTypes) ? 'sensitive' : 'general';
    }

    protected function sanitizePaymentData($data)
    {
        // Remove sensitive payment information but keep metadata
        return [
            'method' => $data['method'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? 'AUD',
            'merchant_id' => isset($data['merchant_id']) ? substr($data['merchant_id'], 0, 8) . '...' : null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'card_last_four' => isset($data['card_number']) ? '***' . substr($data['card_number'], -4) : null,
            'tokenized' => isset($data['token']) && !empty($data['token'])
        ];
    }

    // Compliance report generation methods would be implemented here
    protected function generateGdprReport($startDate, $endDate) { /* Implementation */ }
    protected function generatePciReport($startDate, $endDate) { /* Implementation */ }
    protected function generateSecurityReport($startDate, $endDate) { /* Implementation */ }
    protected function generateAccessReport($startDate, $endDate) { /* Implementation */ }
}