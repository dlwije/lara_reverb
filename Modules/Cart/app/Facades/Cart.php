<?php
namespace Modules\Cart\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Cart\Classes\Cart as CartService;
class Cart extends Facade
{
    /**
     * Class Cart
     *
     * @package Modules\Cart\Facades
     *
     * @see \Modules\Cart\Classes\Cart
     *
     * @method static CartService instance(string|null $instance = null)
     * @method static string currentInstance()
     * @method static CartService|\Modules\Cart\Classes\Cart add($id, $name = null, $qty = null, $price = null, array $options = [])
     * @method static CartService|\Modules\Cart\Classes\Cart update($rowId, $qty)
     * @method static CartService remove($rowId)
     * @method static \Modules\Cart\Classes\Cart|null get($rowId)
     * @method static bool destroy()
     * @method static \Illuminate\Support\Collection content()
     * @method static int count()
     * @method static float total()
     * @method static float subtotal()
     * @method static float tax()
     * @method static \Illuminate\Support\Collection search($search)
     * @method static CartService associate($model)
     * @method static void store($identifier)
     * @method static void restore($identifier)
     * @method static array apiContent()
     */
    protected static function getFacadeAccessor()
    {
//        return BaseCart::class;
        return 'cart';
    }
}
