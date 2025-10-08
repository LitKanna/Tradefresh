<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\Support\Str;

class SqlInjectionProtectionMiddleware
{
    /**
     * SQL injection patterns to detect
     */
    protected $sqlInjectionPatterns = [
        // Common SQL injection patterns
        '/(\bunion\b.*\bselect\b)/i',
        '/(\bselect\b.*\bfrom\b.*\bwhere\b)/i',
        '/(\binsert\b.*\binto\b.*\bvalues\b)/i',
        '/(\bupdate\b.*\bset\b)/i',
        '/(\bdelete\b.*\bfrom\b)/i',
        '/(\bdrop\b.*\btable\b)/i',
        '/(\balter\b.*\btable\b)/i',
        '/(\bcreate\b.*\btable\b)/i',
        '/(\btruncate\b.*\btable\b)/i',
        
        // SQL injection specific patterns
        '/(\bor\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+[\'"]?)/i',
        '/(\band\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+[\'"]?)/i',
        '/(\bor\b\s+[\'"]?[a-z]+[\'"]?\s*=\s*[\'"]?[a-z]+[\'"]?)/i',
        '/(\bwhere\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+[\'"]?)/i',
        '/(\bhaving\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+[\'"]?)/i',
        
        // SQL functions and commands
        '/(\bexec\b|\bexecute\b|\bsp_\w+)/i',
        '/(\bxp_\w+|\bsp_password\b)/i',
        '/(\bsys\w+|\binformation_schema\b)/i',
        '/(\bmysql\b|\buser\b|\bpassword\b)/i',
        
        // SQL comment patterns
        '/(--|\#|\/\*.*\*\/)/i',
        
        // SQL concatenation
        '/(\|\||concat\()/i',
        
        // SQL casting and conversion
        '/(\bcast\b|\bconvert\b|\bchar\b)/i',
        
        // SQL injection bypasses
        '/(\bhex\b|\bunhex\b|\bascii\b|\bord\b)/i',
        '/(\bbenchmark\b|\bsleep\b|\bwaitfor\b)/i',
        
        // Multiple statements
        '/;.*(\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b)/i',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->detectSqlInjection($request)) {
            return $this->handleSqlInjectionAttempt($request);
        }

        return $next($request);
    }

    /**
     * Detect SQL injection attempts in request
     */
    protected function detectSqlInjection(Request $request)
    {
        $input = $this->getAllInputData($request);
        $inputString = json_encode($input, JSON_UNESCAPED_UNICODE);

        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $inputString)) {
                $this->logSqlInjectionAttempt($request, $pattern, $inputString);
                return true;
            }
        }

        return false;
    }

    /**
     * Get all input data from request
     */
    protected function getAllInputData(Request $request)
    {
        return array_merge(
            $request->query(),
            $request->request->all(),
            $request->route() ? $request->route()->parameters() : []
        );
    }

    /**
     * Handle SQL injection attempt
     */
    protected function handleSqlInjectionAttempt(Request $request)
    {
        // Block the request immediately
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'error_code' => 'INVALID_REQUEST'
            ], 400);
        }

        return response()->view('errors.400', [
            'message' => 'Invalid request parameters'
        ], 400);
    }

    /**
     * Log SQL injection attempt
     */
    protected function logSqlInjectionAttempt(Request $request, $pattern, $inputString)
    {
        AuditLog::create([
            'auditable_type' => 'security',
            'auditable_id' => null,
            'user_type' => $request->user() ? get_class($request->user()) : 'unknown',
            'user_id' => $request->user() ? $request->user()->id : null,
            'event' => 'sql_injection_attempt',
            'audit_type' => 'security',
            'old_values' => null,
            'new_values' => null,
            'changed_fields' => null,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'request_data' => $this->sanitizeForLogging($request->all()),
            'response_data' => ['error' => 'SQL injection attempt blocked'],
            'response_code' => 400,
            'session_id' => $request->session() ? $request->session()->getId() : null,
            'correlation_id' => Str::uuid(),
            'tags' => ['security', 'sql_injection', 'blocked'],
            'notes' => 'SQL injection attempt detected and blocked',
            'metadata' => [
                'matched_pattern' => $pattern,
                'suspicious_input' => $this->extractSuspiciousInput($inputString),
                'referer' => $request->header('referer'),
                'risk_level' => $this->calculateRiskLevel($inputString)
            ],
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Sanitize sensitive data for logging
     */
    protected function sanitizeForLogging($data)
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Extract suspicious portions of input for analysis
     */
    protected function extractSuspiciousInput($inputString)
    {
        $suspicious = [];
        
        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match_all($pattern, $inputString, $matches)) {
                $suspicious[] = array_unique($matches[0]);
            }
        }

        return array_merge(...$suspicious);
    }

    /**
     * Calculate risk level based on input patterns
     */
    protected function calculateRiskLevel($inputString)
    {
        $riskScore = 0;
        
        // High-risk patterns
        $highRiskPatterns = [
            '/(\bdrop\b.*\btable\b)/i' => 10,
            '/(\btruncate\b.*\btable\b)/i' => 10,
            '/(\bdelete\b.*\bfrom\b)/i' => 8,
            '/(\bexec\b|\bexecute\b)/i' => 9,
            '/(\bxp_\w+|\bsp_password\b)/i' => 9,
        ];
        
        // Medium-risk patterns
        $mediumRiskPatterns = [
            '/(\bunion\b.*\bselect\b)/i' => 6,
            '/(\bselect\b.*\bfrom\b)/i' => 5,
            '/(\binsert\b.*\binto\b)/i' => 5,
            '/(\bupdate\b.*\bset\b)/i' => 5,
        ];
        
        // Low-risk patterns
        $lowRiskPatterns = [
            '/(\bor\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+[\'"]?)/i' => 3,
            '/(--|\#)/i' => 2,
            '/(\|\||concat\()/i' => 2,
        ];
        
        foreach ($highRiskPatterns as $pattern => $score) {
            if (preg_match($pattern, $inputString)) {
                $riskScore += $score;
            }
        }
        
        foreach ($mediumRiskPatterns as $pattern => $score) {
            if (preg_match($pattern, $inputString)) {
                $riskScore += $score;
            }
        }
        
        foreach ($lowRiskPatterns as $pattern => $score) {
            if (preg_match($pattern, $inputString)) {
                $riskScore += $score;
            }
        }
        
        if ($riskScore >= 10) return 'CRITICAL';
        if ($riskScore >= 6) return 'HIGH';
        if ($riskScore >= 3) return 'MEDIUM';
        return 'LOW';
    }
}