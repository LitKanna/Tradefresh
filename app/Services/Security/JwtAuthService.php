<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;

class JwtAuthService
{
    protected $secretKey;
    protected $algorithm = 'HS256';
    protected $accessTokenTtl = 3600; // 1 hour
    protected $refreshTokenTtl = 604800; // 7 days

    public function __construct()
    {
        $this->secretKey = config('app.jwt_secret', config('app.key'));
    }

    /**
     * Generate JWT tokens for user
     */
    public function generateTokens($user, array $scopes = ['read'], array $metadata = [])
    {
        $jti = Str::uuid()->toString();
        $now = Carbon::now();

        // Generate access token
        $accessPayload = [
            'iss' => config('app.name'),
            'aud' => config('app.url'),
            'sub' => $user->id,
            'user_type' => get_class($user),
            'iat' => $now->timestamp,
            'nbf' => $now->timestamp,
            'exp' => $now->addSeconds($this->accessTokenTtl)->timestamp,
            'jti' => $jti,
            'type' => 'access',
            'scopes' => $scopes,
            'metadata' => array_merge($metadata, [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => session()->getId()
            ])
        ];

        $accessToken = JWT::encode($accessPayload, $this->secretKey, $this->algorithm);

        // Generate refresh token
        $refreshPayload = [
            'iss' => config('app.name'),
            'aud' => config('app.url'),
            'sub' => $user->id,
            'user_type' => get_class($user),
            'iat' => $now->timestamp,
            'nbf' => $now->timestamp,
            'exp' => Carbon::now()->addSeconds($this->refreshTokenTtl)->timestamp,
            'jti' => Str::uuid()->toString(),
            'type' => 'refresh',
            'access_jti' => $jti
        ];

        $refreshToken = JWT::encode($refreshPayload, $this->secretKey, $this->algorithm);

        // Store tokens in cache for validation and blacklisting
        $this->storeTokenData($jti, [
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'scopes' => $scopes,
            'created_at' => $now->toDateTimeString(),
            'expires_at' => $now->addSeconds($this->accessTokenTtl)->toDateTimeString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        // Log token generation
        $this->logSecurityEvent($user, 'jwt_tokens_generated', [
            'jti' => $jti,
            'scopes' => $scopes,
            'expires_in' => $this->accessTokenTtl
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenTtl,
            'scopes' => $scopes
        ];
    }

    /**
     * Validate JWT token
     */
    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            // Check if token is blacklisted
            if ($this->isTokenBlacklisted($payload['jti'])) {
                throw new \Exception('Token has been blacklisted');
            }

            // Verify token data exists in cache
            $tokenData = $this->getTokenData($payload['jti']);
            if (!$tokenData) {
                throw new \Exception('Token data not found');
            }

            // Additional security checks
            $this->performSecurityChecks($payload, $tokenData);

            return [
                'valid' => true,
                'payload' => $payload,
                'token_data' => $tokenData
            ];

        } catch (\Exception $e) {
            $this->logSecurityEvent(null, 'jwt_validation_failed', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 50) . '...',
                'ip' => request()->ip()
            ]);

            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken($refreshToken)
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            if ($payload['type'] !== 'refresh') {
                throw new \Exception('Invalid token type for refresh');
            }

            // Check if refresh token is blacklisted
            if ($this->isTokenBlacklisted($payload['jti'])) {
                throw new \Exception('Refresh token has been blacklisted');
            }

            // Get user
            $userClass = $payload['user_type'];
            $user = $userClass::find($payload['sub']);

            if (!$user) {
                throw new \Exception('User not found');
            }

            // Blacklist the old access token
            if (isset($payload['access_jti'])) {
                $this->blacklistToken($payload['access_jti']);
            }

            // Generate new tokens
            $newTokens = $this->generateTokens($user);

            // Blacklist the refresh token (one-time use)
            $this->blacklistToken($payload['jti']);

            return $newTokens;

        } catch (\Exception $e) {
            $this->logSecurityEvent(null, 'jwt_refresh_failed', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            throw $e;
        }
    }

    /**
     * Blacklist a token
     */
    public function blacklistToken($jti, $reason = 'manual')
    {
        $key = "blacklisted_token:{$jti}";
        Cache::put($key, [
            'blacklisted_at' => now()->toDateTimeString(),
            'reason' => $reason,
            'ip' => request()->ip()
        ], $this->refreshTokenTtl); // Keep blacklist for full token lifetime

        $this->logSecurityEvent(null, 'jwt_token_blacklisted', [
            'jti' => $jti,
            'reason' => $reason
        ]);
    }

    /**
     * Check if token is blacklisted
     */
    public function isTokenBlacklisted($jti)
    {
        return Cache::has("blacklisted_token:{$jti}");
    }

    /**
     * Store token data in cache
     */
    protected function storeTokenData($jti, $data)
    {
        $key = "jwt_token_data:{$jti}";
        Cache::put($key, $data, $this->accessTokenTtl);
    }

    /**
     * Get token data from cache
     */
    protected function getTokenData($jti)
    {
        return Cache::get("jwt_token_data:{$jti}");
    }

    /**
     * Perform additional security checks
     */
    protected function performSecurityChecks($payload, $tokenData)
    {
        // Check IP address (with some flexibility)
        $currentIP = request()->ip();
        $tokenIP = $tokenData['ip'] ?? null;

        if ($tokenIP && !$this->isIPChangeAllowed($tokenIP, $currentIP)) {
            throw new \Exception('Token IP address mismatch');
        }

        // Check user agent (with some flexibility for mobile apps)
        $currentUserAgent = request()->userAgent();
        $tokenUserAgent = $tokenData['user_agent'] ?? null;

        if ($tokenUserAgent && !$this->isUserAgentChangeAllowed($tokenUserAgent, $currentUserAgent)) {
            $this->logSecurityEvent(null, 'jwt_user_agent_change', [
                'jti' => $payload['jti'],
                'original_user_agent' => $tokenUserAgent,
                'current_user_agent' => $currentUserAgent
            ]);
            // Don't throw exception for user agent changes, just log them
        }

        // Check for token replay attacks
        $this->checkTokenReplay($payload['jti']);
    }

    /**
     * Check if IP change is allowed
     */
    protected function isIPChangeAllowed($originalIP, $currentIP)
    {
        // Allow same IP
        if ($originalIP === $currentIP) {
            return true;
        }

        // Allow changes within same /24 subnet for IPv4
        if (filter_var($originalIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
            filter_var($currentIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            
            $originalSubnet = implode('.', array_slice(explode('.', $originalIP), 0, 3));
            $currentSubnet = implode('.', array_slice(explode('.', $currentIP), 0, 3));
            
            return $originalSubnet === $currentSubnet;
        }

        return false;
    }

    /**
     * Check if user agent change is allowed
     */
    protected function isUserAgentChangeAllowed($originalUA, $currentUA)
    {
        // Exact match
        if ($originalUA === $currentUA) {
            return true;
        }

        // Extract browser and version for comparison
        $originalBrowser = $this->extractBrowserInfo($originalUA);
        $currentBrowser = $this->extractBrowserInfo($currentUA);

        // Allow minor version updates
        return $originalBrowser['name'] === $currentBrowser['name'];
    }

    /**
     * Extract browser information from user agent
     */
    protected function extractBrowserInfo($userAgent)
    {
        $browsers = [
            'Chrome' => '/Chrome\/([0-9.]+)/',
            'Firefox' => '/Firefox\/([0-9.]+)/',
            'Safari' => '/Safari\/([0-9.]+)/',
            'Edge' => '/Edge\/([0-9.]+)/',
            'Opera' => '/Opera\/([0-9.]+)/',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $userAgent, $matches)) {
                return [
                    'name' => $name,
                    'version' => $matches[1],
                    'major_version' => explode('.', $matches[1])[0]
                ];
            }
        }

        return ['name' => 'unknown', 'version' => '0', 'major_version' => '0'];
    }

    /**
     * Check for token replay attacks
     */
    protected function checkTokenReplay($jti)
    {
        $key = "jwt_token_usage:{$jti}";
        $usageCount = Cache::increment($key, 1);
        
        if ($usageCount === 1) {
            Cache::expire($key, $this->accessTokenTtl);
        }

        // Allow reasonable number of requests per minute
        if ($usageCount > 100) { // 100 requests per token lifetime
            throw new \Exception('Token usage limit exceeded - possible replay attack');
        }
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeUserTokens($user, $reason = 'user_request')
    {
        // This would typically involve storing a user-specific blacklist
        // For now, we'll use a simple cache-based approach
        $key = "user_tokens_revoked:{$user->id}";
        Cache::put($key, [
            'revoked_at' => now()->toDateTimeString(),
            'reason' => $reason
        ], $this->refreshTokenTtl);

        $this->logSecurityEvent($user, 'jwt_all_tokens_revoked', [
            'reason' => $reason,
            'revoked_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Check if all user tokens are revoked
     */
    public function areUserTokensRevoked($user)
    {
        return Cache::has("user_tokens_revoked:{$user->id}");
    }

    /**
     * Generate API key for long-term access
     */
    public function generateApiKey($user, array $scopes = ['read'], $name = 'API Key')
    {
        $apiKey = 'sm_' . Str::random(40); // Sydney Markets prefix
        $hashedKey = hash('sha256', $apiKey);

        $keyData = [
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'name' => $name,
            'scopes' => $scopes,
            'created_at' => now()->toDateTimeString(),
            'last_used_at' => null,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ];

        // Store hashed key (never store plain key)
        Cache::put("api_key:{$hashedKey}", $keyData, now()->addYears(1));

        $this->logSecurityEvent($user, 'api_key_generated', [
            'name' => $name,
            'scopes' => $scopes,
            'key_preview' => substr($apiKey, 0, 10) . '...'
        ]);

        return [
            'api_key' => $apiKey,
            'scopes' => $scopes,
            'name' => $name,
            'created_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Validate API key
     */
    public function validateApiKey($apiKey)
    {
        $hashedKey = hash('sha256', $apiKey);
        $keyData = Cache::get("api_key:{$hashedKey}");

        if (!$keyData) {
            return ['valid' => false, 'error' => 'Invalid API key'];
        }

        // Update last used timestamp
        $keyData['last_used_at'] = now()->toDateTimeString();
        $keyData['last_used_ip'] = request()->ip();
        Cache::put("api_key:{$hashedKey}", $keyData, now()->addYears(1));

        return [
            'valid' => true,
            'key_data' => $keyData
        ];
    }

    /**
     * Log security events
     */
    protected function logSecurityEvent($user, $event, $metadata = [])
    {
        AuditLog::create([
            'auditable_type' => $user ? get_class($user) : 'security',
            'auditable_id' => $user ? $user->id : null,
            'user_type' => $user ? get_class($user) : 'system',
            'user_id' => $user ? $user->id : null,
            'event' => $event,
            'audit_type' => 'security',
            'old_values' => null,
            'new_values' => null,
            'changed_fields' => null,
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'method' => request()->method(),
            'request_data' => null,
            'response_data' => null,
            'response_code' => null,
            'session_id' => session() ? session()->getId() : null,
            'correlation_id' => Str::uuid(),
            'tags' => ['security', 'jwt', $event],
            'notes' => "JWT authentication event: {$event}",
            'metadata' => $metadata,
            'environment' => app()->environment(),
        ]);
    }
}