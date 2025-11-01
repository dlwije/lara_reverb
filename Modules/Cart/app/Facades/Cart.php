<?php
namespace Modules\Cart\app\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Cart\Models\Cart as BaseCart;
class Cart extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BaseCart::class;
//        return 'cart';
    }
}
