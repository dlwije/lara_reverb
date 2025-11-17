<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PosImageService
{
    /**
     * Check if image exists in POS system
     */
    public function imageExists($filename)
    {
        return Cache::remember("pos_image_exists_{$filename}", 3600, function () use ($filename) {
            $url = $this->generateApiUrl($filename);

            $response = Http::timeout(5)->head($url);

            return $response->successful();
        });
    }

    /**
     * Generate API URL
     */
    private function generateApiUrl($filename)
    {
        $baseUrl = config('services.pos.image_base_url');
        $token = config('services.pos.api_token');

        return $baseUrl . '/images/products/' . $filename . '?token=' . $token;
    }

    /**
     * Batch check multiple images
     */
    public function batchCheckImages(array $filenames)
    {
        $results = [];

        foreach ($filenames as $filename) {
            $results[$filename] = $this->imageExists($filename);
        }

        return $results;
    }
}
