<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImageHelper
{
    /**
     * Convert POS stored URL to API URL
     */
    public static function posImageUrl($posStoredUrl)
    {
        // If it's already a full POS URL, extract filename and convert to API URL
        if (Str::startsWith($posStoredUrl, config('services.pos.url'))) {
            $filename = self::extractFilenameFromUrl($posStoredUrl);
            return self::generateApiUrl($filename);
        }

        // If it's just a filename, generate API URL directly
        return self::generateApiUrl($posStoredUrl);
    }

    /**
     * Extract filename from POS stored URL
     */
    private static function extractFilenameFromUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        return basename($path);
    }

    /**
     * Generate API URL with Sanctum token
     */
    private static function generateApiUrl($filename)
    {
        $baseUrl = config('services.pos.url') . '/api';

        // Determine the appropriate API endpoint based on the original path pattern
        if (Str::contains($filename, 'products/')) {
            $endpoint = '/assets/images/products/' . basename($filename);
        } else {
            $endpoint = '/images/' . $filename;
        }

        return $baseUrl . $endpoint;
    }

    /**
     * Check if image exists via API
     */
    public static function posImageExists($filename)
    {
        return Cache::remember("pos_image_exists_{$filename}", 3600, function () use ($filename) {
            $url = self::generateApiUrl($filename);

            try {
                $response = Http::posApi()->timeout(3)->head($url);
                return $response->successful();
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    /**
     * Get image with fallback
     */
    public static function posImageWithFallback($image, $fallback = null)
    {
        $fallback = $fallback ?? '/images/placeholder.jpg';

        if (empty($image)) {
            return $fallback;
        }

        $imageUrl = self::posImageUrl($image);

        return $imageUrl ?: $fallback;
    }

    /**
     * Get multiple image URLs from array or string
     */
    public static function getPosImageUrls($images)
    {
        if (is_string($images)) {
            return self::posImageUrl($images);
        }

        if (is_array($images)) {
            return array_map(function ($image) {
                return self::posImageUrl($image);
            }, $images);
        }

        return null;
    }
}
