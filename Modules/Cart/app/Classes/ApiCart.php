<?php

namespace Modules\Cart\Classes;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Cart\Interfaces\CartInterface;
use Modules\Cart\Models\Cart as CartModel;

class ApiCart implements CartInterface
{
    const DEFAULT_INSTANCE = 'default';

    protected $instance;
    protected $associatedModel;
    protected $identifier;
    protected $cart;

    public function __construct($identifier = null, $instance = null)
    {
        $this->instance($instance ?? self::DEFAULT_INSTANCE);
        $this->identifier = $identifier;
        $this->initializeCart();
    }

    protected function initializeCart()
    {
        // If no identifier provided, generate new one and create cart
        if (!$this->identifier) {
            $this->identifier = $this->generateIdentifier();
            $this->createNewCart();
            return;
        }

        // Try to load existing cart with provided identifier
        $this->loadExistingCart();

        // If cart doesn't exist with provided identifier, create new one with that identifier
        if (!$this->cart || !$this->cart->exists) {
            $this->createNewCart($this->identifier);
        }
    }

    protected function loadExistingCart()
    {
        $this->cart = CartModel::with(['items' => function($query) {
            $query->whereHas('product');
        }])
            ->forIdentifier($this->identifier)
            ->instance($this->instance)
            ->active()
            ->first();
    }

    protected function createNewCart($identifier = null)
    {
        $identifier = $identifier ?: $this->generateIdentifier();

        $this->cart = CartModel::create([
            'identifier' => $identifier,
            'instance' => $this->instance,
            'user_id' => Auth::id(),
            'currency' => config('app.currency', 'USD'),
            'subtotal' => 0,
            'total' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Initialize empty items relationship
        $this->cart->setRelation('items', new Collection());

        // Update identifier in case it was generated
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getCartModel()
    {
        return $this->cart;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        $this->loadCart();
        return $this;
    }

    public function instance($instance = null)
    {
        $this->instance = $instance ?: self::DEFAULT_INSTANCE;
        return $this;
    }

    public function currentInstance()
    {
        return $this->instance;
    }

    public function add($id, $name = null, $qty = null, $price = null, array $options = [])
    {
        if (is_array($id)) {
            return $this->addArray($id);
        }

        $cartItem = $this->addItem($id, $name, $qty, $price, $options);
        $this->recalculateCartTotals();

        // Dispatch event if needed
        // $this->events->dispatch('cart.added', $cartItem);

        return $cartItem;
    }

    protected function addArray(array $items)
    {
        $cartItems = collect();

        foreach ($items as $item) {
            $cartItems->push($this->addItem(
                $item['id'],
                $item['name'],
                $item['qty'],
                $item['price'],
                $item['options'] ?? []
            ));
        }

        $this->recalculateCartTotals();
        return $cartItems;
    }

    protected function addItem($id, $name, $qty, $price, array $options = [])
    {
        $rowId = $this->generateRowId($id, $options);

        // Check if item already exists
        $existingItem = $this->cart->items()->where('row_id', $rowId)->first();

        if ($existingItem) {
            return $this->updateQty($rowId, $existingItem->qty + $qty);
        }

        // Create new item
        $cartItem = $this->cart->items()->create([
            'row_id' => $rowId,
            'product_id' => $id,
            'name' => $name,
            'qty' => $qty,
            'price' => $price,
            'options' => $options,
            'tax_rate' => 0, // Set default tax rate
            'tax_amount' => 0,
            'discount_amount' => 0,
        ]);

        // Calculate item totals
        $cartItem->calculateTotals();

        return $this->formatCartItem($cartItem);
    }

    public function update($rowId, $qty)
    {
        if ($qty instanceof Closure) {
            $cartItem = $this->get($rowId);
            $qty = $qty($cartItem->qty);
        }

        if ($qty <= 0) {
            return $this->remove($rowId);
        }

        $result = $this->updateQty($rowId, $qty);
        $this->recalculateCartTotals();

        return $result;
    }

    protected function updateQty($rowId, $qty)
    {
        $cartItem = $this->cart->items()->where('row_id', $rowId)->firstOrFail();

        $cartItem->update([
            'qty' => $qty,
            'updated_at' => Carbon::now()
        ]);

        // Recalculate item totals
        $cartItem->calculateTotals();

        // Dispatch event if needed
        // $this->events->dispatch('cart.updated', $this->formatCartItem($cartItem));

        return $this->formatCartItem($cartItem->fresh());
    }

    public function remove($rowId)
    {
        $cartItem = $this->cart->items()->where('row_id', $rowId)->first();

        if ($cartItem) {
            $formattedItem = $this->formatCartItem($cartItem);
            $cartItem->delete();

            $this->recalculateCartTotals();

            // Dispatch event if needed
            // $this->events->dispatch('cart.removed', $formattedItem);
        }

        return $this;
    }

    public function get($rowId)
    {
        $cartItem = $this->cart->items()->where('row_id', $rowId)->firstOrFail();
        return $this->formatCartItem($cartItem);
    }

    public function destroy()
    {
        $this->cart->items()->delete();
        $this->cart->update([
            'subtotal' => 0,
            'total' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
        ]);

        // Dispatch event if needed
        // $this->events->dispatch('cart.destroyed');
    }

    public function content()
    {
        $this->cart->load('items'); // Ensure items are loaded
        return $this->cart->items->map(function ($item) {
            return $this->formatCartItem($item);
        });
    }

    public function count()
    {
        if (!$this->cart->relationLoaded('items')) {
            $this->cart->load('items');
        }
        return $this->cart->items->sum('qty') ?: 0;
    }
    public function itemsCount()
    {
        if (!$this->cart->relationLoaded('items')) {
            $this->cart->load('items');
        }
        return $this->cart->items->count() ?: 0;
    }

    public function total()
    {
        return $this->cart->total;
    }

    public function subtotal()
    {
        return $this->cart->subtotal;
    }

    public function tax()
    {
        return $this->cart->tax_amount;
    }

    public function discount()
    {
        return $this->cart->discount_amount;
    }

    public function shipping()
    {
        return $this->cart->shipping_amount;
    }

    public function search($search)
    {
        if ($search instanceof Closure) {
            return $this->cart->items->filter($search)->map(function ($item) {
                return $this->formatCartItem($item);
            });
        }

        return $this->cart->items->filter(function ($item) use ($search) {
            return stripos($item->product_id, $search) !== false ||
                stripos($item->name, $search) !== false;
        })->map(function ($item) {
            return $this->formatCartItem($item);
        });
    }

    public function associate($model)
    {
        $this->associatedModel = $model;
        return $this;
    }

    public function store($identifier = null)
    {
        // Already stored in database, just update timestamp and recalculate
        $this->recalculateCartTotals();
        $this->cart->touch();
        return $this->cart;
    }

    public function restore($identifier, $merge = false)
    {
        // Not needed for API as cart is always persisted
        return $this;
    }

    public function setShipping($amount)
    {
        $this->cart->update([
            'shipping_amount' => $amount
        ]);
        $this->recalculateCartTotals();
        return $this;
    }

    public function setDiscount($amount, $couponCode = null)
    {
        $this->cart->update([
            'discount_amount' => $amount,
            'coupon_code' => $couponCode
        ]);
        $this->recalculateCartTotals();
        return $this;
    }

    public function setTax($amount)
    {
        $this->cart->update([
            'tax_amount' => $amount
        ]);
        $this->recalculateCartTotals();
        return $this;
    }

    public function apiContent()
    {
        $content = $this->content();

        return [
            'content' => $content->values(),
            'count' => $this->count(),
            'subtotal' => $this->subtotal(),
            'tax' => $this->tax(),
            'discount' => $this->discount(),
            'shipping' => $this->shipping(),
            'total' => $this->total(),
            'instance' => $this->currentInstance(),
            'currency' => $this->cart->currency,
            'identifier' => $this->identifier,
        ];
    }

    protected function loadCart()
    {
        $this->cart = CartModel::with(['items' => function($query) {
            $query->whereHas('product'); // Ensure items have products
        }])
            ->forIdentifier($this->identifier)
            ->instance($this->instance)
            ->active()
            ->first();

        if (!$this->cart) {
            $this->cart = CartModel::create([
                'identifier' => $this->identifier,
                'instance' => $this->instance,
                'user_id' => Auth::id(),
                'currency' => config('app.currency', 'AED'),
                'subtotal' => 0,
                'total' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'shipping_amount' => 0,
                'expires_at' => Carbon::now()->addDays(30), // Cart expires in 30 days
            ]);
        }

        return $this->cart;
    }

    protected function recalculateCartTotals()
    {
        $this->cart->refresh()->load('items');
        $this->cart->recalculateTotals();
    }

    protected function formatCartItem($cartItem)
    {
        return (object) [
            'rowId' => $cartItem->row_id,
            'id' => $cartItem->product_id,
            'name' => $cartItem->name,
            'qty' => $cartItem->qty,
            'price' => $cartItem->price,
            'taxRate' => $cartItem->tax_rate,
            'taxAmount' => $cartItem->tax_amount,
            'discountAmount' => $cartItem->discount_amount,
            'subtotal' => $cartItem->subtotal,
            'total' => $cartItem->total,
            'options' => $cartItem->options ?? [],
            'productAttributes' => $cartItem->product_attributes ?? [],
            'product' => $cartItem->product ?? ($this->associatedModel ? $this->associatedModel::find($cartItem->product_id) : null),
        ];
    }

    protected function generateRowId($id, $options)
    {
        ksort($options);
        return md5($id . serialize($options));
    }

    protected function generateIdentifier()
    {
        return 'api_' . md5(uniqid('cart_', true));
    }

    public function mergeWithUserCart($userId)
    {
        $userCart = CartModel::with('items')
            ->forUser($userId)
            ->instance($this->instance)
            ->active()
            ->first();

        if ($userCart && $userCart->id !== $this->cart->id) {
            // Merge items from user cart to current cart
            foreach ($userCart->items as $userCartItem) {
                $existingItem = $this->cart->items()
                    ->where('row_id', $userCartItem->row_id)
                    ->first();

                if ($existingItem) {
                    $existingItem->update([
                        'qty' => $existingItem->qty + $userCartItem->qty
                    ]);
                    $existingItem->calculateTotals();
                } else {
                    $this->cart->items()->create([
                        'row_id' => $userCartItem->row_id,
                        'product_id' => $userCartItem->product_id,
                        'name' => $userCartItem->name,
                        'qty' => $userCartItem->qty,
                        'price' => $userCartItem->price,
                        'options' => $userCartItem->options,
                        'tax_rate' => $userCartItem->tax_rate,
                        'tax_amount' => $userCartItem->tax_amount,
                        'discount_amount' => $userCartItem->discount_amount,
                    ]);
                }
            }

            // Delete the user cart
            $userCart->delete();

            // Recalculate totals
            $this->recalculateCartTotals();
        }

        // Associate cart with user
        $this->cart->update(['user_id' => $userId]);

        return $this;
    }

    // Add this method to debug cart loading
    public function debug()
    {
        return [
            'identifier' => $this->identifier,
            'cart_exists' => !is_null($this->cart),
            'cart_id' => $this->cart->id ?? null,
            'items_count' => $this->cart->items->count() ?? 0,
            'total_count' => $this->count(),
            'cart_model' => $this->cart ? $this->cart->toArray() : null
        ];
    }
}
