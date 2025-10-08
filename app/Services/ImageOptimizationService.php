<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ImageOptimizationService
{
    /**
     * Supported image formats
     */
    protected $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Optimization settings
     */
    protected $settings = [
        'quality' => 85,
        'max_width' => 1920,
        'max_height' => 1080,
        'convert_to_webp' => true,
    ];

    /**
     * Optimize an image file
     */
    public function optimize($path, $options = [])
    {
        $settings = array_merge($this->settings, $options);

        try {
            $fullPath = public_path($path);

            if (! file_exists($fullPath)) {
                return ['error' => 'File not found'];
            }

            $info = pathinfo($fullPath);
            $extension = strtolower($info['extension']);

            if (! in_array($extension, $this->supportedFormats)) {
                return ['error' => 'Unsupported format'];
            }

            // Get image dimensions
            [$width, $height] = getimagesize($fullPath);

            // Calculate new dimensions if needed
            $newWidth = $width;
            $newHeight = $height;

            if ($width > $settings['max_width']) {
                $ratio = $settings['max_width'] / $width;
                $newWidth = $settings['max_width'];
                $newHeight = $height * $ratio;
            }

            if ($newHeight > $settings['max_height']) {
                $ratio = $settings['max_height'] / $newHeight;
                $newHeight = $settings['max_height'];
                $newWidth = $newWidth * $ratio;
            }

            // Load the image
            $image = $this->loadImage($fullPath, $extension);

            if (! $image) {
                return ['error' => 'Failed to load image'];
            }

            // Resize if needed
            if ($newWidth != $width || $newHeight != $height) {
                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve transparency for PNG and GIF
                if ($extension == 'png' || $extension == 'gif') {
                    imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            // Save optimized image
            $optimizedPath = $this->saveImage($image, $fullPath, $extension, $settings['quality']);

            // Create WebP version if enabled
            if ($settings['convert_to_webp'] && $extension != 'webp') {
                $webpPath = str_replace('.'.$extension, '.webp', $fullPath);
                imagewebp($image, $webpPath, $settings['quality']);
            }

            imagedestroy($image);

            // Calculate size reduction
            $originalSize = filesize($fullPath);
            clearstatcache(true, $fullPath);
            $newSize = filesize($fullPath);
            $reduction = round(($originalSize - $newSize) / $originalSize * 100, 2);

            return [
                'success' => true,
                'original_size' => $this->formatBytes($originalSize),
                'new_size' => $this->formatBytes($newSize),
                'reduction' => $reduction.'%',
                'dimensions' => "{$newWidth}x{$newHeight}",
            ];

        } catch (\Exception $e) {
            Log::error('Image optimization failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return ['error' => 'Optimization failed: '.$e->getMessage()];
        }
    }

    /**
     * Batch optimize images in a directory
     */
    public function optimizeDirectory($directory)
    {
        $results = [];
        $totalSaved = 0;

        $files = glob(public_path($directory).'/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

        foreach ($files as $file) {
            $relativePath = str_replace(public_path().'/', '', $file);
            $result = $this->optimize($relativePath);

            if (isset($result['success'])) {
                $results[] = [
                    'file' => basename($file),
                    'result' => $result,
                ];
            }
        }

        return [
            'processed' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Load image based on type
     */
    protected function loadImage($path, $type)
    {
        switch ($type) {
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($path);
            case 'png':
                return imagecreatefrompng($path);
            case 'gif':
                return imagecreatefromgif($path);
            case 'webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    /**
     * Save image based on type
     */
    protected function saveImage($image, $path, $type, $quality)
    {
        switch ($type) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $path, $quality);
            case 'png':
                return imagepng($image, $path, floor($quality / 10));
            case 'gif':
                return imagegif($image, $path);
            case 'webp':
                return imagewebp($image, $path, $quality);
            default:
                return false;
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision).' '.$units[$i];
    }

    /**
     * Generate responsive image sizes
     */
    public function generateResponsiveSizes($path)
    {
        $sizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 320, 'height' => 240],
            'medium' => ['width' => 640, 'height' => 480],
            'large' => ['width' => 1024, 'height' => 768],
            'xlarge' => ['width' => 1920, 'height' => 1080],
        ];

        $results = [];

        foreach ($sizes as $name => $dimensions) {
            $outputPath = $this->generateSizePath($path, $name);
            $result = $this->optimize($path, $dimensions);
            $results[$name] = $result;
        }

        return $results;
    }

    /**
     * Generate path for sized image
     */
    protected function generateSizePath($path, $size)
    {
        $info = pathinfo($path);

        return $info['dirname'].'/'.$info['filename'].'-'.$size.'.'.$info['extension'];
    }
}
