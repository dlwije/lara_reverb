<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class ImageHelper
{
    /**
     * Convert POS stored URL to direct asset URL
     */
    public static function posImageUrl($posStoredUrl)
    {
        // If it's empty, return null
        if (empty($posStoredUrl)) {
            return null;
        }

        // If it's already a full POS URL with the correct path, return as is
        if (self::isCorrectAssetUrl($posStoredUrl)) {
            return $posStoredUrl;
        }

        // If it's a full POS URL but wrong path, convert to correct asset path
        if (self::isPosStoredUrl($posStoredUrl)) {
            $filename = self::extractFilenameFromUrl($posStoredUrl);
            return self::generateAssetUrl($filename);
        }

        // If it's just a filename, generate asset URL
        return self::generateAssetUrl($posStoredUrl);
    }

    /**
     * Check if the URL is already in the correct asset format
     */
    private static function isCorrectAssetUrl($url)
    {
        $correctPatterns = [
            'https://nposds.orions360.com/asset/images/products/',
            'http://nposds.orions360.com/asset/images/products/',
        ];

        foreach ($correctPatterns as $pattern) {
            if (Str::startsWith($url, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if it's a POS stored URL (any format)
     */
    private static function isPosStoredUrl($url)
    {
        $posDomains = [
            'https://nposds.orions360.com',
            'http://nposds.orions360.com',
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

        return $filename;
    }

    /**
     * Generate direct asset URL
     */
    private static function generateAssetUrl($filename)
    {
        $baseUrl = config('services.pos.url', 'https://nposds.orions360.com');
        $cleanFilename = self::cleanFilename($filename);

        return $baseUrl . '/asset/images/products/' . $cleanFilename;
    }

    /**
     * Clean filename - remove any URL parts that might be included
     */
    private static function cleanFilename($filename)
    {
        // If the filename contains a full URL, extract just the filename part
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
     * Debug method to see URL transformation
     */
    public static function debugUrlTransformation($originalUrl)
    {
        return [
            'original_url' => $originalUrl,
            'is_pos_url' => self::isPosStoredUrl($originalUrl),
            'is_correct_asset_url' => self::isCorrectAssetUrl($originalUrl),
            'extracted_filename' => self::extractFilenameFromUrl($originalUrl),
            'final_url' => self::posImageUrl($originalUrl),
        ];
    }
}
