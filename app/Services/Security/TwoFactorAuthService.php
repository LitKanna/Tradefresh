<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeEmail;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Enable 2FA for user
     */
    public function enableTwoFactor($user)
    {
        if (!$user->two_factor_secret) {
            $user->two_factor_secret = encrypt($this->google2fa->generateSecretKey());
        }

        $user->two_factor_enabled = true;
        $user->two_factor_recovery_codes = encrypt(json_encode($this->generateRecoveryCodes()));
        $user->save();

        $this->logSecurityEvent($user, 'two_factor_enabled', [
            'method' => 'totp',
            'enabled_at' => now()->toDateTimeString()
        ]);

        return [
            'secret' => decrypt($user->two_factor_secret),
            'qr_code' => $this->generateQrCode($user),
            'recovery_codes' => json_decode(decrypt($user->two_factor_recovery_codes), true)
        ];
    }

    /**
     * Disable 2FA for user
     */
    public function disableTwoFactor($user, $code, $password = null)
    {
        // Verify the code before disabling
        if (!$this->verifyCode($user, $code) && !$this->verifyRecoveryCode($user, $code)) {
            $this->logSecurityEvent($user, 'two_factor_disable_failed', [
                'reason' => 'invalid_code',
                'ip' => request()->ip()
            ]);
            throw new \Exception('Invalid verification code');
        }

        // Verify password if provided
        if ($password && !\Hash::check($password, $user->password)) {
            $this->logSecurityEvent($user, 'two_factor_disable_failed', [
                'reason' => 'invalid_password',
                'ip' => request()->ip()
            ]);
            throw new \Exception('Invalid password');
        }

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        $this->logSecurityEvent($user, 'two_factor_disabled', [
            'disabled_at' => now()->toDateTimeString(),
            'ip' => request()->ip()
        ]);

        return true;
    }

    /**
     * Verify TOTP code
     */
    public function verifyCode($user, $code)
    {
        if (!$user->two_factor_enabled || !$user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $code);

        // Log verification attempt
        $this->logSecurityEvent($user, 'two_factor_verification', [
            'success' => $valid,
            'code_length' => strlen($code),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return $valid;
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode($user, $code)
    {
        if (!$user->two_factor_enabled || !$user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        
        foreach ($recoveryCodes as $index => $recoveryCode) {
            if (hash_equals($recoveryCode, $code)) {
                // Remove used recovery code
                unset($recoveryCodes[$index]);
                $user->two_factor_recovery_codes = encrypt(json_encode(array_values($recoveryCodes)));
                $user->save();

                $this->logSecurityEvent($user, 'recovery_code_used', [
                    'remaining_codes' => count($recoveryCodes),
                    'ip' => request()->ip()
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Send 2FA code via email (backup method)
     */
    public function sendEmailCode($user)
    {
        if (!$user->email_verified_at) {
            throw new \Exception('Email must be verified to receive 2FA codes');
        }

        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $key = "2fa_email_code:{$user->id}";
        
        // Store code for 10 minutes
        Cache::put($key, $code, 600);

        // Rate limit email sending
        $rateLimitKey = "2fa_email_rate:{$user->id}";
        if (Cache::get($rateLimitKey, 0) >= 3) {
            throw new \Exception('Too many email codes requested. Please wait before requesting another.');
        }
        
        Cache::increment($rateLimitKey, 1);
        Cache::expire($rateLimitKey, 3600); // 1 hour limit

        Mail::to($user->email)->send(new TwoFactorCodeEmail($code));

        $this->logSecurityEvent($user, 'two_factor_email_sent', [
            'email' => $user->email,
            'ip' => request()->ip()
        ]);

        return true;
    }

    /**
     * Verify email-based 2FA code
     */
    public function verifyEmailCode($user, $code)
    {
        $key = "2fa_email_code:{$user->id}";
        $storedCode = Cache::get($key);

        if (!$storedCode || !hash_equals($storedCode, $code)) {
            $this->logSecurityEvent($user, 'two_factor_email_verification_failed', [
                'provided_code_length' => strlen($code),
                'ip' => request()->ip()
            ]);
            return false;
        }

        Cache::forget($key);

        $this->logSecurityEvent($user, 'two_factor_email_verification_success', [
            'ip' => request()->ip()
        ]);

        return true;
    }

    /**
     * Generate QR code for TOTP setup
     */
    public function generateQrCode($user)
    {
        $secret = decrypt($user->two_factor_secret);
        $appName = config('app.name', 'Sydney Markets B2B');
        $email = $user->email;

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $appName,
            $email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new ImagickImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        $qrCodeImage = $writer->writeString($qrCodeUrl);

        return 'data:image/png;base64,' . base64_encode($qrCodeImage);
    }

    /**
     * Generate recovery codes
     */
    protected function generateRecoveryCodes($count = 8)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(10);
        }
        return $codes;
    }

    /**
     * Check if user needs 2FA verification
     */
    public function requiresTwoFactor($user)
    {
        if (!$user->two_factor_enabled) {
            return false;
        }

        // Check if already verified in current session
        $sessionKey = "2fa_verified:{$user->id}:" . session()->getId();
        return !Cache::has($sessionKey);
    }

    /**
     * Mark 2FA as verified for current session
     */
    public function markAsVerified($user, $rememberFor = 3600)
    {
        $sessionKey = "2fa_verified:{$user->id}:" . session()->getId();
        Cache::put($sessionKey, true, $rememberFor);

        $this->logSecurityEvent($user, 'two_factor_session_verified', [
            'expires_in_seconds' => $rememberFor,
            'session_id' => session()->getId(),
            'ip' => request()->ip()
        ]);
    }

    /**
     * Generate backup codes for user
     */
    public function regenerateRecoveryCodes($user)
    {
        if (!$user->two_factor_enabled) {
            throw new \Exception('2FA must be enabled to generate recovery codes');
        }

        $newCodes = $this->generateRecoveryCodes();
        $user->two_factor_recovery_codes = encrypt(json_encode($newCodes));
        $user->save();

        $this->logSecurityEvent($user, 'recovery_codes_regenerated', [
            'codes_count' => count($newCodes),
            'ip' => request()->ip()
        ]);

        return $newCodes;
    }

    /**
     * Get remaining recovery codes count
     */
    public function getRemainingRecoveryCodesCount($user)
    {
        if (!$user->two_factor_recovery_codes) {
            return 0;
        }

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        return count($codes);
    }

    /**
     * Log security events
     */
    protected function logSecurityEvent($user, $event, $metadata = [])
    {
        AuditLog::create([
            'auditable_type' => get_class($user),
            'auditable_id' => $user->id,
            'user_type' => get_class($user),
            'user_id' => $user->id,
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
            'session_id' => session()->getId(),
            'correlation_id' => Str::uuid(),
            'tags' => ['security', '2fa', $event],
            'notes' => "Two-factor authentication event: {$event}",
            'metadata' => $metadata,
            'environment' => app()->environment(),
        ]);
    }
}