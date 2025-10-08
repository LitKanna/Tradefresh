<?php

namespace App\Services\Security;

class XssFilter
{
    /**
     * Dangerous patterns that indicate XSS attempts
     */
    protected $dangerousPatterns = [
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
        '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
        '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
        '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi',
        '/<applet\b[^<]*(?:(?!<\/applet>)<[^<]*)*<\/applet>/mi',
        '/<meta\b[^>]*>/i',
        '/<form\b[^<]*(?:(?!<\/form>)<[^<]*)*<\/form>/mi',
        '/javascript:/i',
        '/vbscript:/i',
        '/data:text\/html/i',
        '/on\w+\s*=/i', // Event handlers like onclick, onload, etc.
        '/<img[^>]+src[\\s]*=[\\s]*["\']?[\\s]*javascript:/mi',
        '/expression\s*\(/i',
        '/@import/i',
        '/binding\s*:/i',
        '/<link[^>]+href[\\s]*=[\\s]*["\']?[\\s]*javascript:/mi',
        '/&lt;script/i',
        '/&lt;iframe/i',
        '/%3Cscript/i',
        '/%3Ciframe/i',
    ];

    /**
     * Clean input string from XSS attacks
     */
    public function clean($input)
    {
        if (!is_string($input)) {
            return $input;
        }

        // Remove null bytes
        $input = str_replace(chr(0), '', $input);

        // Convert HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);

        // Remove dangerous patterns
        foreach ($this->dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        // Additional cleaning
        $input = $this->removeInvisibleCharacters($input);
        $input = $this->removeBadUnicodeCharacters($input);

        return $input;
    }

    /**
     * Check if string contains suspicious content
     */
    public function isSuspicious($input)
    {
        if (!is_string($input)) {
            return false;
        }

        // Check against dangerous patterns
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        // Check for suspicious character combinations
        $suspiciousChars = [
            '<script',
            '</script>',
            '<iframe',
            '</iframe>',
            'javascript:',
            'vbscript:',
            'data:text/html',
            'expression(',
            'eval(',
            'document.cookie',
            'document.write',
            'window.location',
        ];

        $lowerInput = strtolower($input);
        foreach ($suspiciousChars as $char) {
            if (strpos($lowerInput, $char) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove invisible characters that could be used in attacks
     */
    protected function removeInvisibleCharacters($str, $urlEncoded = true)
    {
        $nonDisplayables = [];

        // Add non-displayable characters
        if ($urlEncoded) {
            $nonDisplayables[] = '/%0[0-8bcef]/i';    // url encoded 00-08, 11, 12, 14, 15
            $nonDisplayables[] = '/%1[0-9a-f]/i';     // url encoded 16-31
        }

        $nonDisplayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';   // 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($nonDisplayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }

    /**
     * Remove bad Unicode characters
     */
    protected function removeBadUnicodeCharacters($str)
    {
        $str = preg_replace('/[\x{FEFF}\x{200B}-\x{200D}\x{FFFE}\x{FFFF}]/u', '', $str);
        return $str;
    }

    /**
     * Clean HTML content while preserving safe tags
     */
    public function cleanHtml($html, $allowedTags = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li'])
    {
        if (!is_string($html)) {
            return $html;
        }

        // Allow only specified tags
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        $cleaned = strip_tags($html, $allowedTagsString);

        // Remove dangerous attributes from allowed tags
        $cleaned = preg_replace('/(<[^>]+)\s+(on\w+|style|class|id)\s*=\s*["\'][^"\']*["\']([^>]*>)/i', '$1$3', $cleaned);

        return $this->clean($cleaned);
    }

    /**
     * Validate and clean URL to prevent XSS through links
     */
    public function cleanUrl($url)
    {
        if (!is_string($url)) {
            return $url;
        }

        // Remove dangerous protocols
        $dangerousProtocols = ['javascript:', 'vbscript:', 'data:', 'file:', 'about:'];
        
        foreach ($dangerousProtocols as $protocol) {
            if (stripos($url, $protocol) === 0) {
                return '';
            }
        }

        // Clean the URL
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}