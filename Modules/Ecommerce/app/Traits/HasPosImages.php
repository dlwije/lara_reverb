<?php

namespace Modules\Ecommerce\Traits;

use App\Helpers\ImageHelper;

trait HasPosImages {
    /**
     * Get the transformed image URL
     */
    public function getImageUrlAttribute()
    {
        $imageField = $this->imageField ?? 'image';

        if (empty($this->{$imageField})) {
            return ImageHelper::posImageWithFallback(null);
        }

        return ImageHelper::posImageWithFallback($this->{$imageField});
    }

    /**
     * Get gallery URLs
     */
    public function getGalleryUrlsAttribute()
    {
        $imagesField = $this->imagesField ?? 'images';

        if (empty($this->{$imagesField})) {
            return [$this->image_url];
        }

        if (is_string($this->{$imagesField})) {
            $imagesArray = json_decode($this->{$imagesField}, true) ?? [$this->{$imagesField}];
        } else {
            $imagesArray = (array) $this->{$imagesField};
        }

        return array_map(function($image) {
            return ImageHelper::posImageWithFallback($image);
        }, $imagesArray);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrlAttribute()
    {
        $galleryUrls = $this->gallery_urls;
        return $galleryUrls[0] ?? $this->image_url;
    }

    /**
     * Check if image exists in POS system
     */
    public function getImageExistsAttribute()
    {
        $imageField = $this->imageField ?? 'image';

        if (empty($this->{$imageField})) {
            return false;
        }

        return ImageHelper::posImageExists($this->{$imageField});
    }

    /**
     * Scope to filter products with images
     */
    public function scopeWithImages($query)
    {
        $imageField = $this->imageField ?? 'image';
        return $query->whereNotNull($imageField)->where($imageField, '!=', '');
    }

    /**
     * Scope to filter products without images
     */
    public function scopeWithoutImages($query)
    {
        $imageField = $this->imageField ?? 'image';
        return $query->whereNull($imageField)->orWhere($imageField, '');
    }
}
