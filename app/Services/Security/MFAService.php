<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\MFASetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Exception;

class MFAService
{
    protected $google2fa;
    protected $maxAttempts = 5;
    protected $lockoutDuration = 30; // minutes
    protected $codeExpiration = 5; // minutes for email/SMS codes

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Enable MFA for a user
     */
    public function enableMFA(User $user, string $method = 'totp'): array
    {
        $mfaSetting = $user->mfaSetting ?? new MFASetting(['user_id' => $user->id]);
        
        switch ($method) {
            case 'totp':
                return $this->setupTOTP($user, $mfaSetting);
            case 'sms':
                return $this->setupSMS($user, $mfaSetting);
            case 'email':
                return $this->setupEmail($user, $mfaSetting);
            case 'backup_codes':
                return $this->generateBackupCodes($user, $mfaSetting);
            default:
                throw new Exception('Invalid MFA method');
        }
    }

    /**
     * Setup TOTP (Google Authenticator)
     */
    protected function setupTOTP(User $user, MFASetting $mfaSetting): array
    {
        $secret = $this->google2fa->generateSecretKey();
        
        $mfaSetting->method = 'totp';
        $mfaSetting->secret = encrypt($secret);
        $mfaSetting->enabled = false; // Will be enabled after verification
        $mfaSetting->save();

        $qrCode = $this->generateQRCode($user, $secret);

        return [
            'method' => 'totp',
            'secret' => $secret,
            'qr_code' => $qrCode,
            'manual_entry_key' => $this->formatSecretForManualEntry($secret),
            'requires_verification' => true
        ];
    }

    /**
     * Generate QR Code for TOTP
     */
    protected function generateQRCode(User $user, string $secret): string
    {
        $companyName = config('app.name', 'BuyerDashboard');
        $email = $user->email;
        
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $companyName,
            $email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        return 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($qrCodeUrl));
    }

    /**
     * Format secret for manual entry
     */
    protected function formatSecretForManualEntry(string $secret): string
    {
        return implode(' ', str_split($secret, 4));
    }

    /**
     * Setup SMS MFA
     */
    protected function setupSMS(User $user, MFASetting $mfaSetting, string $phoneNumber = null): array
    {
        if (!$phoneNumber) {
            throw new Exception('Phone number is required for SMS MFA');
        }

        $mfaSetting->method = 'sms';
        $mfaSetting->phone_number = encrypt($phoneNumber);
        $mfaSetting->enabled = false;
        $mfaSetting->save();

        // Send verification code
        $code = $this->generateVerificationCode();
        $this->sendSMSCode($phoneNumber, $code);
        $this->storeVerificationCode($user->id, $code, 'sms_setup');

        return [
            'method' => 'sms',
            'phone_number' => $this->maskPhoneNumber($phoneNumber),
            'requires_verification' => true,
            'code_sent' => true
        ];
    }

    /**
     * Setup Email MFA
     */
    protected function setupEmail(User $user, MFASetting $mfaSetting, string $email = null): array
    {
        $email = $email ?? $user->email;

        $mfaSetting->method = 'email';
        $mfaSetting->backup_email = $email !== $user->email ? encrypt($email) : null;
        $mfaSetting->enabled = false;
        $mfaSetting->save();

        // Send verification code
        $code = $this->generateVerificationCode();
        $this->sendEmailCode($email, $code, $user);
        $this->storeVerificationCode($user->id, $code, 'email_setup');

        return [
            'method' => 'email',
            'email' => $this->maskEmail($email),
            'requires_verification' => true,
            'code_sent' => true
        ];
    }

    /**
     * Generate backup codes
     */
    public function generateBackupCodes(User $user, MFASetting $mfaSetting = null): array
    {
        $mfaSetting = $mfaSetting ?? $user->mfaSetting;
        
        if (!$mfaSetting) {
            throw new Exception('MFA must be enabled first');
        }

        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }

        $hashedCodes = array_map(fn($code) => bcrypt($code), $codes);
        $mfaSetting->backup_codes = $hashedCodes;
        $mfaSetting->save();

        return [
            'backup_codes' => $codes,
            'warning' => 'Store these codes in a safe place. Each code can only be used once.'
        ];
    }

    /**
     * Verify MFA code
     */
    public function verifyCode(User $user, string $code): bool
    {
        $mfaSetting = $user->mfaSetting;
        
        if (!$mfaSetting || !$mfaSetting->enabled) {
            throw new Exception('MFA is not enabled for this user');
        }

        // Check if account is locked
        if ($this->isAccountLocked($mfaSetting)) {
            throw new Exception('Account is temporarily locked due to too many failed attempts');
        }

        $isValid = false;

        switch ($mfaSetting->method) {
            case 'totp':
                $isValid = $this->verifyTOTP($mfaSetting, $code);
                break;
            case 'sms':
            case 'email':
                $isValid = $this->verifyStoredCode($user->id, $code);
                break;
            case 'backup_codes':
                $isValid = $this->verifyBackupCode($mfaSetting, $code);
                break;
        }

        if ($isValid) {
            $this->resetFailedAttempts($mfaSetting);
            $mfaSetting->last_used_at = now();
            $mfaSetting->save();
        } else {
            $this->incrementFailedAttempts($mfaSetting);
        }

        return $isValid;
    }

    /**
     * Verify TOTP code
     */
    protected function verifyTOTP(MFASetting $mfaSetting, string $code): bool
    {
        $secret = decrypt($mfaSetting->secret);
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Verify stored verification code
     */
    protected function verifyStoredCode(int $userId, string $code): bool
    {
        $cacheKey = "mfa_code_{$userId}";
        $storedData = Cache::get($cacheKey);

        if (!$storedData) {
            return false;
        }

        $isValid = hash_equals($storedData['code'], $code);
        
        if ($isValid) {
            Cache::forget($cacheKey);
        }

        return $isValid;
    }

    /**
     * Verify backup code
     */
    protected function verifyBackupCode(MFASetting $mfaSetting, string $code): bool
    {
        $backupCodes = $mfaSetting->backup_codes ?? [];
        
        foreach ($backupCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used code
                unset($backupCodes[$index]);
                $mfaSetting->backup_codes = array_values($backupCodes);
                $mfaSetting->save();
                return true;
            }
        }

        return false;
    }

    /**
     * Send MFA code via SMS or Email
     */
    public function sendMFACode(User $user): void
    {
        $mfaSetting = $user->mfaSetting;
        
        if (!$mfaSetting || !$mfaSetting->enabled) {
            throw new Exception('MFA is not enabled for this user');
        }

        $code = $this->generateVerificationCode();

        switch ($mfaSetting->method) {
            case 'sms':
                $phoneNumber = decrypt($mfaSetting->phone_number);
                $this->sendSMSCode($phoneNumber, $code);
                break;
            case 'email':
                $email = $mfaSetting->backup_email ? decrypt($mfaSetting->backup_email) : $user->email;
                $this->sendEmailCode($email, $code, $user);
                break;
            default:
                throw new Exception('Code sending not supported for this MFA method');
        }

        $this->storeVerificationCode($user->id, $code, 'mfa_login');
    }

    /**
     * Generate verification code
     */
    protected function generateVerificationCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Store verification code in cache
     */
    protected function storeVerificationCode(int $userId, string $code, string $purpose): void
    {
        Cache::put(
            "mfa_code_{$userId}",
            [
                'code' => $code,
                'purpose' => $purpose,
                'created_at' => now()
            ],
            now()->addMinutes($this->codeExpiration)
        );
    }

    /**
     * Send SMS code (placeholder - integrate with SMS service)
     */
    protected function sendSMSCode(string $phoneNumber, string $code): void
    {
        // Integrate with your SMS service (Twilio, AWS SNS, etc.)
        // For now, log the code
        \Log::info("SMS MFA Code for {$phoneNumber}: {$code}");
    }

    /**
     * Send email code
     */
    protected function sendEmailCode(string $email, string $code, User $user): void
    {
        Mail::to($email)->send(new \App\Mail\MFACode($code, $user));
    }

    /**
     * Check if account is locked
     */
    protected function isAccountLocked(MFASetting $mfaSetting): bool
    {
        if (!$mfaSetting->locked_until) {
            return false;
        }

        if (Carbon::parse($mfaSetting->locked_until)->isPast()) {
            $mfaSetting->locked_until = null;
            $mfaSetting->failed_attempts = 0;
            $mfaSetting->save();
            return false;
        }

        return true;
    }

    /**
     * Increment failed attempts
     */
    protected function incrementFailedAttempts(MFASetting $mfaSetting): void
    {
        $mfaSetting->failed_attempts++;
        
        if ($mfaSetting->failed_attempts >= $this->maxAttempts) {
            $mfaSetting->locked_until = now()->addMinutes($this->lockoutDuration);
        }
        
        $mfaSetting->save();
    }

    /**
     * Reset failed attempts
     */
    protected function resetFailedAttempts(MFASetting $mfaSetting): void
    {
        $mfaSetting->failed_attempts = 0;
        $mfaSetting->locked_until = null;
        $mfaSetting->save();
    }

    /**
     * Disable MFA for user
     */
    public function disableMFA(User $user): void
    {
        $mfaSetting = $user->mfaSetting;
        
        if ($mfaSetting) {
            $mfaSetting->enabled = false;
            $mfaSetting->secret = null;
            $mfaSetting->backup_codes = null;
            $mfaSetting->save();
        }
    }

    /**
     * Mask phone number for display
     */
    protected function maskPhoneNumber(string $phone): string
    {
        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
    }

    /**
     * Mask email for display
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(1, strlen($name) - 4)) . substr($name, -2);
        
        return $maskedName . '@' . $domain;
    }

    /**
     * Check if user has MFA enabled
     */
    public function isMFAEnabled(User $user): bool
    {
        return $user->mfaSetting && $user->mfaSetting->enabled;
    }

    /**
     * Get MFA status for user
     */
    public function getMFAStatus(User $user): array
    {
        $mfaSetting = $user->mfaSetting;
        
        if (!$mfaSetting) {
            return [
                'enabled' => false,
                'method' => null
            ];
        }

        return [
            'enabled' => $mfaSetting->enabled,
            'method' => $mfaSetting->method,
            'verified' => $mfaSetting->verified_at !== null,
            'last_used' => $mfaSetting->last_used_at,
            'backup_codes_count' => count($mfaSetting->backup_codes ?? [])
        ];
    }
}