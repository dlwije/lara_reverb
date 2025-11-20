<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageHelper
{
    /**
     * Convert POS stored URL to API URL
     */
    public static function posImageUrl($posStoredUrl)
    {
        // If it's empty, return null
        if (empty($posStoredUrl)) {
            return null;
        }

        // If it's already a full POS URL, extract filename and convert to API URL
        if (self::isPosStoredUrl($posStoredUrl)) {
            $filename = self::extractFilenameFromUrl($posStoredUrl);
            return self::generateApiUrl($filename);
        }

        // If it's just a filename, generate API URL directly
        return self::generateApiUrl($posStoredUrl);
    }

    /**
     * Check if the URL is a POS stored URL
     */
    private static function isPosStoredUrl($url)
    {
        $posDomains = [
            'https://nposds.orions360.com',
            'http://nposds.orions360.com',
            'https://st_mana_beta5.test',
            'http://st_mana_beta5.test',
            // Add other possible POS domains here
        ];

        foreach ($posDomains as $domain) {
            if (Str::startsWith($url, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract filename from POS stored URL
     */
    private static function extractFilenameFromUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);

        // Remove any leading paths and get just the filename
        $filename = basename($path);

        // Debug log to see what's being extracted
        Log::info('Extracted filename', [
            'original_url' => $url,
            'path' => $path,
            'filename' => $filename
        ]);

        return $filename;
    }

    /**
     * Generate API URL with Sanctum token
     */
    private static function generateApiUrl($filename)
    {
        $baseUrl = config('services.pos.url') . '/api';
        $cleanFilename = self::cleanFilename($filename);

        // Use public endpoint to avoid authentication issues
//        $endpoint = '/public/images/products/' . $cleanFilename;

        // If you want to use authenticated endpoint, include token
         $endpoint = '/images/products/' . $cleanFilename;
         $apiUrl = $baseUrl . $endpoint . '?token=' . config('services.pos.api_token');

//        $apiUrl = $baseUrl . $endpoint;

        return $apiUrl;
    }

    /**
     * Clean filename - remove any URL parts that might be included
     */
    private static function cleanFilename($filename)
    {
        // If the filename still contains a full URL, extract just the filename part
        if (filter_var($filename, FILTER_VALIDATE_URL)) {
            $path = parse_url($filename, PHP_URL_PATH);
            return basename($path);
        }

        // Remove any path segments and get just the filename
        return basename($filename);
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
     * Check if image exists via API
     */
    public static function posImageExists($filename)
    {
        // Extract clean filename if it's a full URL
        $cleanFilename = self::cleanFilename($filename);

        $cacheKey = "pos_image_exists_{$cleanFilename}";

        return Cache::remember($cacheKey, 3600, function () use ($cleanFilename) {
            $url = self::generateApiUrl($cleanFilename);

            try {
                $response = Http::posApi()->timeout(3)->head($url);
                return $response->successful();
            } catch (\Exception $e) {
                Log::warning('POS image check failed', [
                    'filename' => $cleanFilename,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
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

    /**
     * Debug method to see what's happening with URL transformation
     */
    public static function debugUrlTransformation($originalUrl)
    {
        return [
            'original_url' => $originalUrl,
            'is_pos_url' => self::isPosStoredUrl($originalUrl),
            'extracted_filename' => self::extractFilenameFromUrl($originalUrl),
            'clean_filename' => self::cleanFilename($originalUrl),
            'final_api_url' => self::posImageUrl($originalUrl),
        ];
    }
}
