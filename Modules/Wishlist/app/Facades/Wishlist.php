<?php

namespace Modules\Wishlist\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Wishlist\Models\Wishlist as BaseWishlist;

class Wishlist extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BaseWishlist::class;
//        return 'wishlist';
    }
}
