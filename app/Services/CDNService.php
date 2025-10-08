<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CDNService
{
    /**
     * CDN configuration
     */
    protected $cdnUrl;

    protected $enabled;

    protected $version;

    public function __construct()
    {
        $this->cdnUrl = config('cdn.url', '');
        $this->enabled = config('cdn.enabled', false);
        $this->version = config('app.version', '1.0.0');
    }

    /**
     * Get CDN URL for an asset
     */
    public function asset($path)
    {
        // If CDN is not enabled, return local path
        if (! $this->enabled) {
            return asset($path);
        }

        // Remove leading slash if present
        $path = ltrim($path, '/');

        // Add version parameter for cache busting
        $versionParam = '?v='.$this->version;

        // Return CDN URL
        return $this->cdnUrl.'/'.$path.$versionParam;
    }

    /**
     * Preload critical CSS
     */
    public function preloadCriticalCss()
    {
        return [
            $this->asset('build/assets/css/app.css'),
            $this->asset('assets/css/global/global-professional.css'),
            $this->asset('assets/css/global/modern-design-system.css'),
        ];
    }

    /**
     * Get optimized image URL
     */
    public function image($path, $width = null, $height = null)
    {
        // If CDN is not enabled, return local path
        if (! $this->enabled) {
            return asset($path);
        }

        // Build image optimization parameters
        $params = [];
        if ($width) {
            $params[] = "w={$width}";
        }
        if ($height) {
            $params[] = "h={$height}";
        }
        $params[] = 'q=85'; // Quality 85%
        $params[] = 'format=auto'; // Auto format (WebP where supported)

        // Remove leading slash if present
        $path = ltrim($path, '/');

        // Return optimized CDN URL
        return $this->cdnUrl.'/optimize?'.implode('&', $params).'&src='.$path;
    }

    /**
     * Generate integrity hash for asset
     */
    public function integrity($path)
    {
        $cacheKey = 'cdn_integrity_'.md5($path);

        return Cache::remember($cacheKey, 86400, function () use ($path) {
            $fullPath = public_path(ltrim($path, '/'));

            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);

                return 'sha384-'.base64_encode(hash('sha384', $content, true));
            }

            return '';
        });
    }

    /**
     * Push assets to CDN (for deployment)
     */
    public function pushAssets()
    {
        // This would be implemented based on your CDN provider
        // For now, we'll just return a success message
        return [
            'status' => 'success',
            'message' => 'Assets ready for CDN deployment',
            'assets' => [
                'css' => glob(public_path('assets/css/**/*.css')),
                'js' => glob(public_path('assets/js/**/*.js')),
                'images' => glob(public_path('images/**/*')),
            ],
        ];
    }
}
