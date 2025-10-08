<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiCacheMiddleware
{
    /**
     * Handle an incoming request and cache API responses
     */
    public function handle(Request $request, Closure $next, ...$parameters)
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Parse middleware parameters
        $ttl = (int)($parameters[0] ?? 300); // Default 5 minutes
        $vary = $parameters[1] ?? null; // Cache variation parameter

        // Generate cache key
        $cacheKey = $this->generateCacheKey($request, $vary);

        // Check for cached response
        $cachedResponse = Cache::store('api')->get($cacheKey);
        
        if ($cachedResponse) {
            $response = response($cachedResponse['content'], $cachedResponse['status']);
            
            // Set cached headers
            foreach ($cachedResponse['headers'] as $header => $value) {
                $response->header($header, $value);
            }
            
            // Add cache headers
            $response->header('X-Cache', 'HIT');
            $response->header('X-Cache-Key', $cacheKey);
            
            return $response;
        }

        // Process request
        $response = $next($request);

        // Only cache successful responses
        if ($response->status() >= 200 && $response->status() < 300) {
            $this->cacheResponse($cacheKey, $response, $ttl);
        }

        // Add cache miss header
        $response->header('X-Cache', 'MISS');
        $response->header('X-Cache-Key', $cacheKey);

        return $response;
    }

    /**
     * Generate cache key based on request
     */
    private function generateCacheKey(Request $request, $vary = null)
    {
        $uri = $request->getRequestUri();
        $query = $request->query();
        
        // Remove pagination from cache key for better hit rates
        unset($query['page']);
        
        // Sort query parameters for consistent keys
        ksort($query);
        
        $keyParts = [
            'api_cache',
            md5($uri),
            md5(serialize($query))
        ];

        // Add user-specific variation if needed
        if ($vary === 'user' && $request->user()) {
            $keyParts[] = 'user:' . $request->user()->id;
        }

        // Add vendor/buyer context if authenticated
        if ($request->user()) {
            $userType = $request->user()->getMorphClass();
            $keyParts[] = $userType . ':' . $request->user()->id;
        }

        return implode(':', $keyParts);
    }

    /**
     * Cache the response
     */
    private function cacheResponse(string $cacheKey, SymfonyResponse $response, int $ttl)
    {
        $content = $response->getContent();
        
        // Compress content if it's large
        if (strlen($content) > 1024) {
            $content = gzencode($content, 6);
            $compressed = true;
        } else {
            $compressed = false;
        }

        $cacheData = [
            'content' => $content,
            'status' => $response->getStatusCode(),
            'headers' => $this->getCacheableHeaders($response),
            'compressed' => $compressed,
            'created_at' => now()->toISOString()
        ];

        Cache::store('api')->put($cacheKey, $cacheData, $ttl);
    }

    /**
     * Get headers that should be cached
     */
    private function getCacheableHeaders(SymfonyResponse $response): array
    {
        $cacheableHeaders = [
            'Content-Type',
            'Content-Encoding',
            'ETag',
            'Last-Modified',
            'Expires',
            'Cache-Control'
        ];

        $headers = [];
        foreach ($cacheableHeaders as $header) {
            if ($response->headers->has($header)) {
                $headers[$header] = $response->headers->get($header);
            }
        }

        return $headers;
    }
}