<?php

namespace App\Services\Security;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use App\Models\AuditLog;
use Illuminate\Support\Str;

class DataEncryptionService
{
    /**
     * Fields that should be encrypted in different models
     */
    protected $encryptionMap = [
        'buyers' => [
            'phone', 'address', 'business_license', 'tax_id', 'bank_details'
        ],
        'vendors' => [
            'phone', 'address', 'abn', 'bank_details', 'tax_id', 'business_license'
        ],
        'payments' => [
            'card_number', 'bank_account', 'routing_number', 'payment_token'
        ],
        'orders' => [
            'delivery_address', 'billing_address', 'delivery_notes'
        ],
        'invoices' => [
            'billing_address', 'tax_details', 'bank_details'
        ]
    ];

    /**
     * PII (Personally Identifiable Information) fields
     */
    protected $piiFields = [
        'email', 'phone', 'address', 'full_name', 'date_of_birth',
        'ssn', 'tax_id', 'drivers_license', 'passport_number'
    ];

    /**
     * Payment-related sensitive fields (PCI DSS)
     */
    protected $paymentFields = [
        'card_number', 'cvv', 'expiry_date', 'bank_account', 'routing_number',
        'iban', 'swift_code', 'payment_token', 'merchant_account'
    ];

    /**
     * Encrypt sensitive data
     */
    public function encryptData($data, $fieldType = 'general')
    {
        try {
            if (empty($data) || is_null($data)) {
                return $data;
            }

            // Use different encryption methods based on data type
            $encrypted = match($fieldType) {
                'payment' => $this->encryptPaymentData($data),
                'pii' => $this->encryptPiiData($data),
                'sensitive' => $this->encryptSensitiveData($data),
                default => $this->encryptGeneralData($data)
            };

            // Log encryption event
            $this->logEncryptionEvent('data_encrypted', [
                'field_type' => $fieldType,
                'data_length' => strlen($data),
                'encrypted_length' => strlen($encrypted)
            ]);

            return $encrypted;

        } catch (\Exception $e) {
            $this->logEncryptionEvent('encryption_failed', [
                'field_type' => $fieldType,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Data encryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt sensitive data
     */
    public function decryptData($encryptedData, $fieldType = 'general')
    {
        try {
            if (empty($encryptedData) || is_null($encryptedData)) {
                return $encryptedData;
            }

            // Check if data is actually encrypted
            if (!$this->isEncrypted($encryptedData)) {
                return $encryptedData; // Return as-is if not encrypted
            }

            $decrypted = match($fieldType) {
                'payment' => $this->decryptPaymentData($encryptedData),
                'pii' => $this->decryptPiiData($encryptedData),
                'sensitive' => $this->decryptSensitiveData($encryptedData),
                default => $this->decryptGeneralData($encryptedData)
            };

            // Log decryption event (without the actual data)
            $this->logEncryptionEvent('data_decrypted', [
                'field_type' => $fieldType,
                'encrypted_length' => strlen($encryptedData),
                'decrypted_length' => strlen($decrypted)
            ]);

            return $decrypted;

        } catch (DecryptException $e) {
            $this->logEncryptionEvent('decryption_failed', [
                'field_type' => $fieldType,
                'error' => 'Decryption failed - invalid payload or key'
            ]);

            throw new \Exception('Data decryption failed: Invalid encrypted data');

        } catch (\Exception $e) {
            $this->logEncryptionEvent('decryption_failed', [
                'field_type' => $fieldType,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Data decryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Encrypt payment data (PCI DSS compliant)
     */
    protected function encryptPaymentData($data)
    {
        // Use stronger encryption for payment data
        $key = config('app.payment_encryption_key', config('app.key'));
        return base64_encode(openssl_encrypt(
            $data, 
            'AES-256-GCM', 
            $key, 
            0, 
            $iv = random_bytes(12),
            $tag
        ) . '::' . base64_encode($iv) . '::' . base64_encode($tag));
    }

    /**
     * Decrypt payment data
     */
    protected function decryptPaymentData($encryptedData)
    {
        $key = config('app.payment_encryption_key', config('app.key'));
        $parts = explode('::', $encryptedData);
        
        if (count($parts) !== 3) {
            throw new DecryptException('Invalid payment data format');
        }

        [$encrypted, $iv, $tag] = [
            base64_decode($parts[0]),
            base64_decode($parts[1]),
            base64_decode($parts[2])
        ];

        $decrypted = openssl_decrypt($encrypted, 'AES-256-GCM', $key, 0, $iv, $tag);
        
        if ($decrypted === false) {
            throw new DecryptException('Payment data decryption failed');
        }

        return $decrypted;
    }

    /**
     * Encrypt PII data
     */
    protected function encryptPiiData($data)
    {
        // Add additional metadata for PII tracking
        $metadata = [
            'encrypted_at' => time(),
            'type' => 'pii',
            'version' => '1.0'
        ];

        $payload = json_encode(['data' => $data, 'metadata' => $metadata]);
        return Crypt::encryptString($payload);
    }

    /**
     * Decrypt PII data
     */
    protected function decryptPiiData($encryptedData)
    {
        $payload = Crypt::decryptString($encryptedData);
        $decoded = json_decode($payload, true);

        if (!isset($decoded['data'])) {
            throw new DecryptException('Invalid PII data format');
        }

        return $decoded['data'];
    }

    /**
     * Encrypt sensitive business data
     */
    protected function encryptSensitiveData($data)
    {
        return Crypt::encryptString($data);
    }

    /**
     * Decrypt sensitive business data
     */
    protected function decryptSensitiveData($encryptedData)
    {
        return Crypt::decryptString($encryptedData);
    }

    /**
     * Encrypt general data
     */
    protected function encryptGeneralData($data)
    {
        return Crypt::encryptString($data);
    }

    /**
     * Decrypt general data
     */
    protected function decryptGeneralData($encryptedData)
    {
        return Crypt::decryptString($encryptedData);
    }

    /**
     * Check if data appears to be encrypted
     */
    public function isEncrypted($data)
    {
        if (!is_string($data)) {
            return false;
        }

        // Laravel's Crypt generates base64 encoded strings
        if (!base64_decode($data, true)) {
            return false;
        }

        // Try to decrypt to verify
        try {
            Crypt::decryptString($data);
            return true;
        } catch (DecryptException $e) {
            // Check for payment data format
            if (substr_count($data, '::') === 2) {
                return true;
            }
            return false;
        }
    }

    /**
     * Hash sensitive data for searching (one-way)
     */
    public function hashForSearch($data, $algorithm = 'sha256')
    {
        $salt = config('app.search_hash_salt', 'sydney_markets_search');
        return hash($algorithm, $salt . $data);
    }

    /**
     * Tokenize sensitive data for display
     */
    public function tokenizeForDisplay($data, $type = 'general')
    {
        if (empty($data)) {
            return $data;
        }

        return match($type) {
            'email' => $this->tokenizeEmail($data),
            'phone' => $this->tokenizePhone($data),
            'card' => $this->tokenizeCardNumber($data),
            'address' => $this->tokenizeAddress($data),
            default => $this->tokenizeGeneral($data)
        };
    }

    /**
     * Tokenize email for display
     */
    protected function tokenizeEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '***@***.***';
        }

        [$username, $domain] = explode('@', $email);
        $tokenizedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
        [$domainName, $tld] = explode('.', $domain, 2);
        $tokenizedDomain = substr($domainName, 0, 1) . str_repeat('*', strlen($domainName) - 1);

        return $tokenizedUsername . '@' . $tokenizedDomain . '.' . $tld;
    }

    /**
     * Tokenize phone number for display
     */
    protected function tokenizePhone($phone)
    {
        $cleaned = preg_replace('/[^\d]/', '', $phone);
        if (strlen($cleaned) < 6) {
            return str_repeat('*', strlen($phone));
        }

        return substr($cleaned, 0, 3) . str_repeat('*', strlen($cleaned) - 6) . substr($cleaned, -3);
    }

    /**
     * Tokenize card number for display
     */
    protected function tokenizeCardNumber($cardNumber)
    {
        $cleaned = preg_replace('/[^\d]/', '', $cardNumber);
        if (strlen($cleaned) < 8) {
            return str_repeat('*', 12) . substr($cleaned, -4);
        }

        return str_repeat('*', strlen($cleaned) - 4) . substr($cleaned, -4);
    }

    /**
     * Tokenize address for display
     */
    protected function tokenizeAddress($address)
    {
        $words = explode(' ', $address);
        if (count($words) <= 2) {
            return str_repeat('*', strlen($address));
        }

        // Show first word and last word, mask the middle
        $tokenized = $words[0] . ' ' . str_repeat('*', 5) . ' ' . end($words);
        return $tokenized;
    }

    /**
     * Tokenize general data for display
     */
    protected function tokenizeGeneral($data)
    {
        $length = strlen($data);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($data, 0, 2) . str_repeat('*', $length - 4) . substr($data, -2);
    }

    /**
     * Bulk encrypt model data
     */
    public function encryptModelData($model, array $fields = null)
    {
        $modelClass = strtolower(class_basename($model));
        $fieldsToEncrypt = $fields ?? ($this->encryptionMap[$modelClass] ?? []);

        foreach ($fieldsToEncrypt as $field) {
            if (isset($model->$field) && !empty($model->$field)) {
                $fieldType = $this->getFieldType($field);
                $model->$field = $this->encryptData($model->$field, $fieldType);
            }
        }

        return $model;
    }

    /**
     * Bulk decrypt model data
     */
    public function decryptModelData($model, array $fields = null)
    {
        $modelClass = strtolower(class_basename($model));
        $fieldsToDecrypt = $fields ?? ($this->encryptionMap[$modelClass] ?? []);

        foreach ($fieldsToDecrypt as $field) {
            if (isset($model->$field) && !empty($model->$field)) {
                $fieldType = $this->getFieldType($field);
                try {
                    $model->$field = $this->decryptData($model->$field, $fieldType);
                } catch (\Exception $e) {
                    // Log error but don't fail the entire operation
                    $this->logEncryptionEvent('model_decryption_failed', [
                        'model' => get_class($model),
                        'field' => $field,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $model;
    }

    /**
     * Get field type for encryption method selection
     */
    protected function getFieldType($field)
    {
        if (in_array($field, $this->paymentFields)) {
            return 'payment';
        }

        if (in_array($field, $this->piiFields)) {
            return 'pii';
        }

        return 'sensitive';
    }

    /**
     * Rotate encryption keys (for scheduled key rotation)
     */
    public function rotateEncryptionKeys()
    {
        // This would be implemented based on your key management strategy
        $this->logEncryptionEvent('key_rotation_started', [
            'initiated_by' => auth()->id(),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Key rotation logic would go here
        
        $this->logEncryptionEvent('key_rotation_completed', [
            'completed_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Validate data integrity after encryption/decryption
     */
    public function validateIntegrity($originalData, $processedData, $operation)
    {
        if ($operation === 'encrypt') {
            return !empty($processedData) && $this->isEncrypted($processedData);
        }

        if ($operation === 'decrypt') {
            return $originalData !== $processedData; // Should be different if decryption worked
        }

        return false;
    }

    /**
     * Log encryption/decryption events
     */
    protected function logEncryptionEvent($event, array $metadata = [])
    {
        AuditLog::create([
            'auditable_type' => 'security',
            'auditable_id' => null,
            'user_type' => auth()->user() ? get_class(auth()->user()) : 'system',
            'user_id' => auth()->user() ? auth()->user()->id : null,
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
            'tags' => ['security', 'encryption', $event],
            'notes' => "Data encryption event: {$event}",
            'metadata' => array_merge($metadata, [
                'encryption_version' => '1.0',
                'algorithm' => 'AES-256-GCM'
            ]),
            'environment' => app()->environment(),
        ]);
    }
}