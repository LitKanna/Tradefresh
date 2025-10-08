<?php

namespace App\Services\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileUploadValidationService
{
    /**
     * Allowed file types and their MIME types
     */
    protected $allowedTypes = [
        'image' => [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
        ],
        'document' => [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'application/csv'],
        ],
        'archive' => [
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'rar' => ['application/x-rar-compressed'],
        ]
    ];

    /**
     * Dangerous file signatures (magic bytes)
     */
    protected $dangerousSignatures = [
        '4D5A' => 'executable', // PE/COFF executable
        '7F454C46' => 'elf', // ELF executable
        'CAFEBABE' => 'java_class', // Java class file
        'D0CF11E0A1B11AE1' => 'ole_compound', // OLE compound (could be malicious Office doc)
        '504B0304' => 'zip_check', // ZIP file (needs further checking)
        '52617221' => 'rar_check', // RAR file (needs further checking)
    ];

    /**
     * Maximum file sizes by type (in bytes)
     */
    protected $maxFileSizes = [
        'image' => 5242880, // 5MB
        'document' => 10485760, // 10MB
        'archive' => 52428800, // 50MB
        'default' => 2097152, // 2MB
    ];

    /**
     * Validate uploaded file
     */
    public function validateFile(UploadedFile $file, $allowedCategories = ['image', 'document'])
    {
        $validationResult = [
            'valid' => false,
            'errors' => [],
            'file_info' => $this->getFileInfo($file),
            'security_scan' => []
        ];

        try {
            // Basic file validation
            $this->validateBasicFile($file);
            
            // File type validation
            $this->validateFileType($file, $allowedCategories);
            
            // File size validation
            $this->validateFileSize($file, $allowedCategories);
            
            // Security scanning
            $securityScan = $this->performSecurityScan($file);
            $validationResult['security_scan'] = $securityScan;
            
            if (!$securityScan['safe']) {
                throw new FileException('File failed security scan: ' . implode(', ', $securityScan['threats']));
            }
            
            // Content validation
            $this->validateFileContent($file);
            
            $validationResult['valid'] = true;
            
        } catch (\Exception $e) {
            $validationResult['errors'][] = $e->getMessage();
            
            $this->logSecurityEvent('file_upload_blocked', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }

        return $validationResult;
    }

    /**
     * Validate basic file properties
     */
    protected function validateBasicFile(UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new FileException('Invalid file upload');
        }

        $filename = $file->getClientOriginalName();
        
        // Check for dangerous file names
        if (preg_match('/[<>:"|?*]/', $filename)) {
            throw new FileException('Filename contains dangerous characters');
        }

        // Check for hidden files or system files
        if (str_starts_with($filename, '.') || str_starts_with($filename, '__')) {
            throw new FileException('Hidden or system files are not allowed');
        }

        // Check for double extensions
        if (substr_count($filename, '.') > 1) {
            throw new FileException('Files with multiple extensions are not allowed');
        }

        // Check filename length
        if (strlen($filename) > 255) {
            throw new FileException('Filename too long');
        }
    }

    /**
     * Validate file type and MIME type
     */
    protected function validateFileType(UploadedFile $file, $allowedCategories)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        $isAllowed = false;
        
        foreach ($allowedCategories as $category) {
            if (isset($this->allowedTypes[$category])) {
                foreach ($this->allowedTypes[$category] as $allowedExt => $allowedMimes) {
                    if ($extension === $allowedExt && in_array($mimeType, $allowedMimes)) {
                        $isAllowed = true;
                        break 2;
                    }
                }
            }
        }
        
        if (!$isAllowed) {
            throw new FileException("File type not allowed: {$extension} ({$mimeType})");
        }
    }

    /**
     * Validate file size
     */
    protected function validateFileSize(UploadedFile $file, $allowedCategories)
    {
        $fileSize = $file->getSize();
        $maxSize = $this->maxFileSizes['default'];
        
        foreach ($allowedCategories as $category) {
            if (isset($this->maxFileSizes[$category]) && $this->maxFileSizes[$category] > $maxSize) {
                $maxSize = $this->maxFileSizes[$category];
            }
        }
        
        if ($fileSize > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            throw new FileException("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }
    }

    /**
     * Perform comprehensive security scan
     */
    protected function performSecurityScan(UploadedFile $file)
    {
        $result = [
            'safe' => true,
            'threats' => [],
            'scans_performed' => []
        ];

        // Magic byte signature check
        $signatureCheck = $this->checkFileSignature($file);
        $result['scans_performed'][] = 'signature_check';
        if (!$signatureCheck['safe']) {
            $result['safe'] = false;
            $result['threats'] = array_merge($result['threats'], $signatureCheck['threats']);
        }

        // Embedded script detection
        $scriptCheck = $this->checkForEmbeddedScripts($file);
        $result['scans_performed'][] = 'script_detection';
        if (!$scriptCheck['safe']) {
            $result['safe'] = false;
            $result['threats'] = array_merge($result['threats'], $scriptCheck['threats']);
        }

        // Virus scan (if ClamAV is available)
        $virusCheck = $this->performVirusScan($file);
        $result['scans_performed'][] = 'virus_scan';
        if (!$virusCheck['safe']) {
            $result['safe'] = false;
            $result['threats'] = array_merge($result['threats'], $virusCheck['threats']);
        }

        // Metadata examination
        $metadataCheck = $this->checkFileMetadata($file);
        $result['scans_performed'][] = 'metadata_check';
        if (!$metadataCheck['safe']) {
            $result['safe'] = false;
            $result['threats'] = array_merge($result['threats'], $metadataCheck['threats']);
        }

        return $result;
    }

    /**
     * Check file signature (magic bytes)
     */
    protected function checkFileSignature(UploadedFile $file)
    {
        $result = ['safe' => true, 'threats' => []];
        
        $handle = fopen($file->getPathname(), 'rb');
        if ($handle === false) {
            return $result;
        }

        $header = fread($handle, 16);
        fclose($handle);

        $hex = strtoupper(bin2hex($header));

        foreach ($this->dangerousSignatures as $signature => $threat) {
            if (str_starts_with($hex, $signature)) {
                $result['safe'] = false;
                $result['threats'][] = "Dangerous file signature detected: {$threat}";
            }
        }

        return $result;
    }

    /**
     * Check for embedded scripts or malicious content
     */
    protected function checkForEmbeddedScripts(UploadedFile $file)
    {
        $result = ['safe' => true, 'threats' => []];
        
        $content = file_get_contents($file->getPathname(), false, null, 0, 8192); // Read first 8KB
        
        $dangerousPatterns = [
            '/<script/i' => 'JavaScript code',
            '/<iframe/i' => 'Iframe element',
            '/javascript:/i' => 'JavaScript protocol',
            '/vbscript:/i' => 'VBScript code',
            '/on\w+\s*=/i' => 'Event handler',
            '/eval\s*\(/i' => 'Eval function',
            '/exec\s*\(/i' => 'Exec function',
            '/<\?php/i' => 'PHP code',
            '/<%/i' => 'Server-side script',
            '/\$_[A-Z]+/i' => 'PHP superglobal'
        ];

        foreach ($dangerousPatterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                $result['safe'] = false;
                $result['threats'][] = "Embedded script detected: {$description}";
            }
        }

        return $result;
    }

    /**
     * Perform virus scan using ClamAV (if available)
     */
    protected function performVirusScan(UploadedFile $file)
    {
        $result = ['safe' => true, 'threats' => []];

        // Check if ClamAV is available
        if (!function_exists('exec') || !$this->isClamAvAvailable()) {
            $result['threats'][] = 'Virus scanner not available';
            return $result;
        }

        $filePath = escapeshellarg($file->getPathname());
        $output = [];
        $returnCode = 0;

        exec("clamscan --no-summary --infected {$filePath} 2>&1", $output, $returnCode);

        if ($returnCode === 1) { // Virus found
            $result['safe'] = false;
            $result['threats'][] = 'Virus detected: ' . implode(' ', $output);
        } elseif ($returnCode === 2) { // Scanner error
            $result['threats'][] = 'Virus scanner error';
        }

        return $result;
    }

    /**
     * Check if ClamAV is available
     */
    protected function isClamAvAvailable()
    {
        $output = [];
        exec('which clamscan 2>/dev/null', $output);
        return !empty($output);
    }

    /**
     * Check file metadata for suspicious content
     */
    protected function checkFileMetadata(UploadedFile $file)
    {
        $result = ['safe' => true, 'threats' => []];

        // For images, check EXIF data
        if (str_starts_with($file->getMimeType(), 'image/')) {
            if (function_exists('exif_read_data')) {
                $exifData = @exif_read_data($file->getPathname());
                if ($exifData && isset($exifData['Software'])) {
                    $software = strtolower($exifData['Software']);
                    $suspiciousSoftware = ['metasploit', 'exploit', 'hack', 'payload'];
                    
                    foreach ($suspiciousSoftware as $suspicious) {
                        if (strpos($software, $suspicious) !== false) {
                            $result['safe'] = false;
                            $result['threats'][] = "Suspicious software in metadata: {$software}";
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Validate file content based on type
     */
    protected function validateFileContent(UploadedFile $file)
    {
        $mimeType = $file->getMimeType();

        // Additional validation for images
        if (str_starts_with($mimeType, 'image/')) {
            $this->validateImageContent($file);
        }
        
        // Additional validation for documents
        if (str_starts_with($mimeType, 'application/pdf') || 
            str_contains($mimeType, 'document') || 
            str_contains($mimeType, 'sheet')) {
            $this->validateDocumentContent($file);
        }
    }

    /**
     * Validate image content
     */
    protected function validateImageContent(UploadedFile $file)
    {
        $imageInfo = @getimagesize($file->getPathname());
        
        if ($imageInfo === false) {
            throw new FileException('Invalid image file');
        }

        // Check image dimensions
        $maxWidth = config('upload.max_image_width', 4096);
        $maxHeight = config('upload.max_image_height', 4096);

        if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
            throw new FileException('Image dimensions exceed maximum allowed size');
        }

        // Verify MIME type matches image data
        $detectedMime = $imageInfo['mime'];
        if ($detectedMime !== $file->getMimeType()) {
            throw new FileException('Image MIME type mismatch');
        }
    }

    /**
     * Validate document content
     */
    protected function validateDocumentContent(UploadedFile $file)
    {
        // Basic PDF validation
        if ($file->getMimeType() === 'application/pdf') {
            $content = file_get_contents($file->getPathname(), false, null, 0, 1024);
            if (!str_starts_with($content, '%PDF-')) {
                throw new FileException('Invalid PDF file');
            }
        }
    }

    /**
     * Get comprehensive file information
     */
    protected function getFileInfo(UploadedFile $file)
    {
        return [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'hash_md5' => md5_file($file->getPathname()),
            'hash_sha256' => hash_file('sha256', $file->getPathname()),
        ];
    }

    /**
     * Sanitize filename
     */
    public function sanitizeFilename($filename)
    {
        // Remove dangerous characters
        $filename = preg_replace('/[<>:"|?*]/', '', $filename);
        
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        
        // Remove multiple dots except the last one
        $parts = explode('.', $filename);
        if (count($parts) > 2) {
            $extension = array_pop($parts);
            $basename = str_replace('.', '_', implode('_', $parts));
            $filename = $basename . '.' . $extension;
        }
        
        // Ensure filename is not too long
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $basename = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250 - strlen($extension));
            $filename = $basename . '.' . $extension;
        }
        
        return $filename;
    }

    /**
     * Log security events
     */
    protected function logSecurityEvent($event, $metadata = [])
    {
        AuditLog::create([
            'auditable_type' => 'security',
            'auditable_id' => null,
            'user_type' => auth()->user() ? get_class(auth()->user()) : 'unknown',
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
            'session_id' => session()->getId(),
            'correlation_id' => Str::uuid(),
            'tags' => ['security', 'file_upload', $event],
            'notes' => "File upload security event: {$event}",
            'metadata' => $metadata,
            'environment' => app()->environment(),
        ]);
    }
}