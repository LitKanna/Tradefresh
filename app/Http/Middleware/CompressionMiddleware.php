<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressionMiddleware
{
    /**
     * Handle an incoming request and compress responses
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only compress if client supports it
        if (!$this->clientSupportsCompression($request)) {
            return $response;
        }

        // Only compress successful responses with content
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return $response;
        }

        $content = $response->getContent();
        
        // Only compress if content is worth compressing
        if (empty($content) || strlen($content) < 1024) {
            return $response;
        }

        // Don't compress already compressed content
        if ($this->isAlreadyCompressed($response)) {
            return $response;
        }

        // Compress based on accepted encoding
        $acceptEncoding = $request->header('Accept-Encoding', '');
        
        if (strpos($acceptEncoding, 'gzip') !== false) {
            $compressedContent = gzencode($content, 6); // Compression level 6 for good balance
            $response->setContent($compressedContent);
            $response->header('Content-Encoding', 'gzip');
            $response->header('Content-Length', strlen($compressedContent));
        } elseif (strpos($acceptEncoding, 'deflate') !== false && function_exists('gzdeflate')) {
            $compressedContent = gzdeflate($content, 6);
            $response->setContent($compressedContent);
            $response->header('Content-Encoding', 'deflate');
            $response->header('Content-Length', strlen($compressedContent));
        }

        // Add Vary header for caching proxies
        $response->header('Vary', 'Accept-Encoding');

        return $response;
    }

    /**
     * Check if client supports compression
     */
    private function clientSupportsCompression(Request $request): bool
    {
        $acceptEncoding = $request->header('Accept-Encoding', '');
        return strpos($acceptEncoding, 'gzip') !== false || strpos($acceptEncoding, 'deflate') !== false;
    }

    /**
     * Check if response is already compressed
     */
    private function isAlreadyCompressed(Response $response): bool
    {
        $contentEncoding = $response->headers->get('Content-Encoding');
        return !empty($contentEncoding) && in_array($contentEncoding, ['gzip', 'deflate', 'br']);
    }
}