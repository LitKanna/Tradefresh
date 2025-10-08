<?php

namespace App\Services\Security;

use App\Models\EncryptionKey;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\AES;
use Carbon\Carbon;
use Exception;

class EncryptionService
{
    protected $defaultAlgorithm = 'AES-256-GCM';
    protected $rsaKeySize = 4096;
    protected $keyRotationDays = 90;
    protected $activeKeys = [];

    /**
     * Encrypt data using field-level encryption
     */
    public function encryptField($data, string $purpose = 'general'): array
    {
        $key = $this->getActiveKey($purpose);
        
        if (!$key) {
            $key = $this->generateKey($purpose);
        }

        $encrypted = $this->encrypt($data, $key);

        return [
            'encrypted_data' => $encrypted,
            'key_id' => $key->key_id,
            'algorithm' => $key->algorithm
        ];
    }

    /**
     * Decrypt field-level encrypted data
     */
    public function decryptField(string $encryptedData, string $keyId): mixed
    {
        $key = EncryptionKey::where('key_id', $keyId)->first();
        
        if (!$key) {
            throw new Exception('Encryption key not found');
        }

        if ($key->status === 'revoked') {
            throw new Exception('Encryption key has been revoked');
        }

        return $this->decrypt($encryptedData, $key);
    }

    /**
     * Encrypt data with specific key
     */
    protected function encrypt($data, EncryptionKey $key): string
    {
        switch ($key->algorithm) {
            case 'AES-256-GCM':
                return $this->encryptAES($data, $key);
            case 'RSA':
                return $this->encryptRSA($data, $key);
            default:
                return Crypt::encryptString(json_encode($data));
        }
    }

    /**
     * Decrypt data with specific key
     */
    protected function decrypt(string $encryptedData, EncryptionKey $key): mixed
    {
        switch ($key->algorithm) {
            case 'AES-256-GCM':
                return $this->decryptAES($encryptedData, $key);
            case 'RSA':
                return $this->decryptRSA($encryptedData, $key);
            default:
                return json_decode(Crypt::decryptString($encryptedData), true);
        }
    }

    /**
     * AES-256-GCM encryption
     */
    protected function encryptAES($data, EncryptionKey $key): string
    {
        $cipher = new AES('gcm');
        $keyData = $this->getDecryptedPrivateKey($key);
        $cipher->setKey(hex2bin($keyData));
        
        $iv = random_bytes(16);
        $cipher->setNonce($iv);
        
        $plaintext = json_encode($data);
        $ciphertext = $cipher->encrypt($plaintext);
        $tag = $cipher->getTag();
        
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * AES-256-GCM decryption
     */
    protected function decryptAES(string $encryptedData, EncryptionKey $key): mixed
    {
        $cipher = new AES('gcm');
        $keyData = $this->getDecryptedPrivateKey($key);
        $cipher->setKey(hex2bin($keyData));
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $ciphertext = substr($data, 32);
        
        $cipher->setNonce($iv);
        $cipher->setTag($tag);
        
        $plaintext = $cipher->decrypt($ciphertext);
        
        return json_decode($plaintext, true);
    }

    /**
     * RSA encryption
     */
    protected function encryptRSA($data, EncryptionKey $key): string
    {
        $rsa = RSA::loadPublicKey($key->public_key);
        $plaintext = json_encode($data);
        
        // For large data, use hybrid encryption
        if (strlen($plaintext) > 245) { // RSA max size for 2048-bit key
            return $this->hybridEncrypt($plaintext, $key);
        }
        
        return base64_encode($rsa->encrypt($plaintext));
    }

    /**
     * RSA decryption
     */
    protected function decryptRSA(string $encryptedData, EncryptionKey $key): mixed
    {
        $privateKey = $this->getDecryptedPrivateKey($key);
        $rsa = RSA::loadPrivateKey($privateKey);
        
        // Check if it's hybrid encrypted
        if (strpos($encryptedData, '::') !== false) {
            return $this->hybridDecrypt($encryptedData, $key);
        }
        
        $plaintext = $rsa->decrypt(base64_decode($encryptedData));
        
        return json_decode($plaintext, true);
    }

    /**
     * Hybrid encryption for large data
     */
    protected function hybridEncrypt(string $data, EncryptionKey $key): string
    {
        // Generate AES key
        $aesKey = bin2hex(random_bytes(32));
        
        // Encrypt data with AES
        $cipher = new AES('gcm');
        $cipher->setKey(hex2bin($aesKey));
        $iv = random_bytes(16);
        $cipher->setNonce($iv);
        
        $ciphertext = $cipher->encrypt($data);
        $tag = $cipher->getTag();
        
        // Encrypt AES key with RSA
        $rsa = RSA::loadPublicKey($key->public_key);
        $encryptedKey = base64_encode($rsa->encrypt($aesKey));
        
        // Combine encrypted key and data
        return $encryptedKey . '::' . base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Hybrid decryption
     */
    protected function hybridDecrypt(string $encryptedData, EncryptionKey $key): mixed
    {
        list($encryptedKey, $encryptedContent) = explode('::', $encryptedData, 2);
        
        // Decrypt AES key with RSA
        $privateKey = $this->getDecryptedPrivateKey($key);
        $rsa = RSA::loadPrivateKey($privateKey);
        $aesKey = $rsa->decrypt(base64_decode($encryptedKey));
        
        // Decrypt data with AES
        $cipher = new AES('gcm');
        $cipher->setKey(hex2bin($aesKey));
        
        $data = base64_decode($encryptedContent);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $ciphertext = substr($data, 32);
        
        $cipher->setNonce($iv);
        $cipher->setTag($tag);
        
        $plaintext = $cipher->decrypt($ciphertext);
        
        return json_decode($plaintext, true);
    }

    /**
     * Generate new encryption key
     */
    public function generateKey(string $purpose, string $algorithm = null): EncryptionKey
    {
        $algorithm = $algorithm ?? $this->defaultAlgorithm;
        $keyId = Str::uuid()->toString();
        
        switch ($algorithm) {
            case 'RSA':
                $keys = $this->generateRSAKeyPair();
                $publicKey = $keys['public'];
                $privateKey = $keys['private'];
                break;
            case 'AES-256-GCM':
            default:
                $publicKey = null;
                $privateKey = bin2hex(random_bytes(32));
                break;
        }

        $encryptionKey = EncryptionKey::create([
            'key_id' => $keyId,
            'algorithm' => $algorithm,
            'public_key' => $publicKey,
            'encrypted_private_key' => Crypt::encryptString($privateKey),
            'purpose' => $purpose,
            'status' => 'active',
            'expires_at' => now()->addDays($this->keyRotationDays),
            'created_by' => Auth::id()
        ]);

        $this->activeKeys[$purpose] = $encryptionKey;

        return $encryptionKey;
    }

    /**
     * Generate RSA key pair
     */
    protected function generateRSAKeyPair(): array
    {
        $key = RSA::createKey($this->rsaKeySize);
        
        return [
            'public' => $key->getPublicKey()->toString('PKCS8'),
            'private' => $key->toString('PKCS8')
        ];
    }

    /**
     * Get active encryption key for purpose
     */
    protected function getActiveKey(string $purpose): ?EncryptionKey
    {
        if (isset($this->activeKeys[$purpose])) {
            return $this->activeKeys[$purpose];
        }

        $key = EncryptionKey::where('purpose', $purpose)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($key) {
            $this->activeKeys[$purpose] = $key;
        }

        return $key;
    }

    /**
     * Get decrypted private key
     */
    protected function getDecryptedPrivateKey(EncryptionKey $key): string
    {
        return Crypt::decryptString($key->encrypted_private_key);
    }

    /**
     * Rotate encryption key
     */
    public function rotateKey(string $purpose): array
    {
        $oldKey = $this->getActiveKey($purpose);
        
        if ($oldKey) {
            $oldKey->status = 'rotated';
            $oldKey->rotated_at = now();
            $oldKey->save();
        }

        $newKey = $this->generateKey($purpose, $oldKey ? $oldKey->algorithm : null);

        // Re-encrypt data if needed
        $reEncryptedCount = $this->reEncryptData($oldKey, $newKey);

        return [
            'old_key_id' => $oldKey ? $oldKey->key_id : null,
            'new_key_id' => $newKey->key_id,
            're_encrypted_records' => $reEncryptedCount
        ];
    }

    /**
     * Re-encrypt data with new key
     */
    protected function reEncryptData(?EncryptionKey $oldKey, EncryptionKey $newKey): int
    {
        if (!$oldKey) {
            return 0;
        }

        // This would need to be implemented based on your specific models
        // that use encryption. Example:
        
        $count = 0;
        // Find all records encrypted with old key
        // Decrypt with old key
        // Encrypt with new key
        // Update records
        
        return $count;
    }

    /**
     * Revoke encryption key
     */
    public function revokeKey(string $keyId): void
    {
        $key = EncryptionKey::where('key_id', $keyId)->first();
        
        if (!$key) {
            throw new Exception('Encryption key not found');
        }

        if ($key->status === 'revoked') {
            throw new Exception('Key is already revoked');
        }

        $key->status = 'revoked';
        $key->save();

        unset($this->activeKeys[$key->purpose]);
    }

    /**
     * Encrypt file
     */
    public function encryptFile(string $filePath, string $purpose = 'file_storage'): array
    {
        if (!file_exists($filePath)) {
            throw new Exception('File not found');
        }

        $key = $this->getActiveKey($purpose) ?? $this->generateKey($purpose);
        
        $fileContent = file_get_contents($filePath);
        $encrypted = $this->encrypt($fileContent, $key);
        
        $encryptedPath = $filePath . '.encrypted';
        file_put_contents($encryptedPath, $encrypted);
        
        // Securely delete original file
        $this->secureDelete($filePath);

        return [
            'encrypted_path' => $encryptedPath,
            'key_id' => $key->key_id,
            'original_size' => strlen($fileContent),
            'encrypted_size' => strlen($encrypted)
        ];
    }

    /**
     * Decrypt file
     */
    public function decryptFile(string $encryptedPath, string $keyId): string
    {
        if (!file_exists($encryptedPath)) {
            throw new Exception('Encrypted file not found');
        }

        $encryptedContent = file_get_contents($encryptedPath);
        $decrypted = $this->decryptField($encryptedContent, $keyId);
        
        $decryptedPath = str_replace('.encrypted', '.decrypted', $encryptedPath);
        file_put_contents($decryptedPath, $decrypted);

        return $decryptedPath;
    }

    /**
     * Securely delete file
     */
    protected function secureDelete(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $fileSize = filesize($filePath);
        
        // Overwrite file with random data multiple times
        $handle = fopen($filePath, 'r+');
        for ($i = 0; $i < 3; $i++) {
            fseek($handle, 0);
            fwrite($handle, random_bytes($fileSize));
            fflush($handle);
        }
        fclose($handle);
        
        // Delete the file
        unlink($filePath);
    }

    /**
     * Hash sensitive data
     */
    public function hashData(string $data, string $salt = null): string
    {
        $salt = $salt ?? bin2hex(random_bytes(16));
        return hash('sha256', $salt . $data . config('app.key'));
    }

    /**
     * Verify hashed data
     */
    public function verifyHash(string $data, string $hash, string $salt): bool
    {
        return hash_equals($hash, $this->hashData($data, $salt));
    }

    /**
     * Tokenize sensitive data
     */
    public function tokenize(string $data, string $purpose = 'tokenization'): string
    {
        $token = Str::random(32);
        
        // Store mapping in secure storage
        $encrypted = $this->encryptField($data, $purpose);
        
        \Cache::put(
            "token_{$token}",
            [
                'encrypted_data' => $encrypted['encrypted_data'],
                'key_id' => $encrypted['key_id']
            ],
            now()->addHours(24)
        );

        return $token;
    }

    /**
     * Detokenize data
     */
    public function detokenize(string $token): mixed
    {
        $data = \Cache::get("token_{$token}");
        
        if (!$data) {
            throw new Exception('Token not found or expired');
        }

        return $this->decryptField($data['encrypted_data'], $data['key_id']);
    }

    /**
     * Get encryption statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_keys' => EncryptionKey::count(),
            'active_keys' => EncryptionKey::where('status', 'active')->count(),
            'rotated_keys' => EncryptionKey::where('status', 'rotated')->count(),
            'revoked_keys' => EncryptionKey::where('status', 'revoked')->count(),
            'keys_by_purpose' => EncryptionKey::groupBy('purpose')
                ->selectRaw('purpose, COUNT(*) as count')
                ->pluck('count', 'purpose'),
            'keys_by_algorithm' => EncryptionKey::groupBy('algorithm')
                ->selectRaw('algorithm, COUNT(*) as count')
                ->pluck('count', 'algorithm'),
            'expiring_soon' => EncryptionKey::where('status', 'active')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])
                ->count()
        ];
    }
}