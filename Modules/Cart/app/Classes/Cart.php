<?php

namespace Modules\Cart\Classes;

use Carbon\Carbon;
use Closure;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Cart\Interfaces\CartInterface;
use Modules\Cart\Models\Cart as CartModel;
use Modules\Cart\Models\CartItem;

class Cart implements CartInterface
{
    const DEFAULT_INSTANCE = 'default';

    protected $session;
    protected $events;
    protected $instance;
    protected $associatedModel;
    protected $identifier; // Add identifier property

    public function __construct(SessionManager $session, $events = null, $instance = null)
    {
        $this->session = $session;
        $this->events = $events;
        $this->instance($instance ?? self::DEFAULT_INSTANCE);
        $this->initializeSessionIdentifier(); // Initialize session identifier
    }

    public function instance($instance = null)
    {
        $instance = $instance ?: self::DEFAULT_INSTANCE;

        $this->instance = sprintf('%s.%s', 'cart', $instance);

        return $this;
    }

    public function currentInstance()
    {
        return str_replace('cart.', '', $this->instance);
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    protected function initializeSessionIdentifier()
    {
        Log::info('all_session: ',$this->session->all());
        // Get or create session identifier
        $sessionKey = $this->instance . '_identifier';

        if (!$this->session->has($sessionKey)) {
            $this->identifier = $this->generateIdentifier();
            Log::info('sessionKey: ' . $sessionKey);
            Log::info('identifier: ' . $this->identifier);
            $this->session->put($sessionKey, $this->identifier);
        } else {
            $this->identifier = $this->session->get($sessionKey);
        }
    }

    public function add($id, $name = null, $qty = null, $price = null, array $options = [])
    {
        if (is_array($id)) {
            $cartItem = $this->addArray($id);
        } else {
            $cartItem = $this->addItem($id, $name, $qty, $price, $options);
        }

        $this->events->dispatch('cart.added', $cartItem);

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

        return $cartItems;
    }

    protected function addItem($id, $name, $qty, $price, array $options = [])
    {
        $rowId = $this->generateRowId($id, $options);

        if ($this->hasRowId($rowId)) {
            $cartItem = $this->get($rowId);
            return $this->updateQty($rowId, $cartItem->qty + $qty);
        }

        $cartItem = $this->createCartItem($id, $name, $qty, $price, $options, $rowId);

        $content = $this->getContent();
        $content->put($rowId, $cartItem);

        $this->setLastUpdatedAt();
        $this->session->put($this->instance, $content);

        return $cartItem;
    }

    public function setLastUpdatedAt(): void
    {
        $this->session->put($this->instance . '_updated_at', Carbon::now());
    }

    public function update($rowId, $qty)
    {
        $cartItem = $this->get($rowId);

        if ($qty instanceof Closure) {
            $qty = $qty($cartItem->qty);
        }

        if ($this->associatedModel && $this->associatedModel::find($cartItem->id)) {
            $cartItem->qty = $qty;
            $cartItem->total = $qty * $cartItem->tax;

            $this->updateCartItem($rowId, $cartItem);

            $this->events->dispatch('cart.updated', $cartItem);

            return $cartItem;
        }

        return $this->updateQty($rowId, $qty);
    }

    protected function updateQty($rowId, $qty)
    {
        if ($qty <= 0) {
            return $this->remove($rowId);
        }

        $cartItem = $this->get($rowId);
        $cartItem->qty = $qty;
        $cartItem->total = $qty * $cartItem->price;
        $cartItem->totalTax = $qty * $cartItem->tax;

        $content = $this->getContent();
        $content->put($rowId, $cartItem);

        $this->session->put($this->instance, $content);

        $this->events->dispatch('cart.updated', $cartItem);

        return $cartItem;
    }

    protected function updateCartItem($rowId, $cartItem)
    {
        $content = $this->getContent();
        $content->put($rowId, $cartItem);
        $this->session->put($this->instance, $content);
    }

    public function remove($rowId)
    {
        $cartItem = $this->get($rowId);

        $content = $this->getContent();
        $content->forget($rowId);

        $this->session->put($this->instance, $content);

        $this->events->dispatch('cart.removed', $cartItem);

        return $this;
    }

    public function get($rowId)
    {
        $content = $this->getContent();

        if (!$content->has($rowId)) {
            throw new \InvalidArgumentException("The cart does not contain rowId {$rowId}.");
        }

        return $content->get($rowId);
    }

    public function destroy()
    {
        $this->session->remove($this->instance);
        $this->session->remove($this->instance . '_identifier');
        $this->session->remove($this->instance . '_updated_at');
        $this->events->dispatch('cart.destroyed');
    }

    public function content()
    {
        return $this->getContent();
    }

    public function count()
    {
        return $this->getContent()->sum('qty');
    }

    public function itemsCount()
    {
        $content = $this->getContent();
        return $content->count();
    }

    public function total()
    {
        $content = $this->getContent();

        $total = $content->reduce(function ($total, $cartItem) {
            return $total + ($cartItem->qty * $cartItem->price);
        }, 0);

        return $total;
    }

    public function subtotal()
    {
        $content = $this->getContent();

        $subtotal = $content->reduce(function ($subtotal, $cartItem) {
            return $subtotal + ($cartItem->qty * $cartItem->price);
        }, 0);

        return $subtotal;
    }

    public function tax()
    {
        // Implement tax calculation based on your requirements
        return 0;
    }

    public function search($search)
    {
        if ($search instanceof Closure) {
            return $this->getContent()->filter($search);
        }

        return $this->getContent()->filter(function ($cartItem) use ($search) {
            return stripos($cartItem->id, $search) !== false ||
                stripos($cartItem->name, $search) !== false;
        });
    }

    public function associate($model)
    {
        $this->associatedModel = $model;
        return $this;
    }

    /**
     * Check if cart already exists in database
     */
    public function existsInDatabase($identifier = null)
    {
        $identifier = $identifier ?: $this->identifier;

        return CartModel::where('identifier', $identifier)
            ->where('instance', $this->currentInstance())
            ->exists();
    }

    /**
     * Get existing database cart
     */
    public function getExistingDatabaseCart($identifier = null)
    {
        $identifier = $identifier ?: $this->identifier;

        return CartModel::with('items')
            ->where('identifier', $identifier)
            ->where('instance', $this->currentInstance())
            ->first();
    }

    /**
     * Store session cart to database (with duplicate prevention)
     */
    public function store($identifier = null, $forceCreate = false)
    {
        // Use provided identifier or session identifier
        $identifier = $identifier ?: $this->identifier;

        Log::info($identifier);

        $content = $this->getContent();

        if ($content->isEmpty()) {
            return null;
        }

        // If forceCreate is false, check if cart already exists
        if (!$forceCreate && $this->existsInDatabase($identifier)) {
            $cart = $this->getExistingDatabaseCart($identifier);

            // Check if this cart is already completed/ordered
            if ($this->isCartCompleted($cart)) {
                // Cart is completed, create a new one with new identifier
                $newIdentifier = $this->generateIdentifier();
                $this->updateSessionIdentifier($newIdentifier);
                return $this->createNewDatabaseCart($newIdentifier, $content);
            } else {
                // Cart exists but not completed - update it
                return $this->updateExistingCart($cart, $content);
            }
        } else {
            // Create new cart
            return $this->createNewDatabaseCart($identifier, $content);
        }
    }

    /**
     * Update existing cart in database
     */
    protected function updateExistingCart($cart, $content)
    {
        // Clear existing items
        $cart->items()->delete();

        // Store individual items
        foreach ($content as $rowId => $item) {
            $qty = $item->qty;
            $price = $item->price;
            $subtotal = $qty * $price;
            $taxAmount = $subtotal * ($item->taxRate ?? 0) / 100;
            $discountAmount = $item->discountAmount ?? 0;
            $total = ($subtotal - $discountAmount) + $taxAmount;
            CartItem::create([
                'cart_id' => $cart->id,
                'row_id' => $rowId,
                'product_id' => $item->id,
                'name' => $item->name,
                'qty' => $item->qty,
                'price' => $item->price,
                'options' => json_encode($item->options),
                'subtotal' => $subtotal ?? 0,
                'tax_rate' => $item->taxRate ?? 0,
                'tax_amount' => $item->taxAmount ?? 0,
                'discount_amount' => $item->discountAmount ?? 0,
                'total' => $total ?? 0,
            ]);
        }

        // Recalculate and update cart totals
        $this->recalculateCartTotals($cart);

        return $cart;
    }

    /**
     * Create new cart in database
     */
    protected function createNewDatabaseCart($identifier, $content)
    {
        $cart = CartModel::create([
            'identifier' => $identifier,
            'instance' => $this->currentInstance(),
            'user_id' => Auth::id() ?? null,
            'currency' => config('app.currency', 'AED'),
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Store individual items
        foreach ($content as $rowId => $item) {
            $qty = $item->qty;
            $price = $item->price;
            $subtotal = $qty * $price;
            $taxAmount = $subtotal * ($item->taxRate ?? 0) / 100;
            $discountAmount = $item->discountAmount ?? 0;
            $total = ($subtotal - $discountAmount) + $taxAmount;

            CartItem::create([
                'cart_id' => $cart->id,
                'row_id' => $rowId,
                'product_id' => $item->id,
                'name' => $item->name,
                'qty' => $qty,
                'price' => $price,
                'options' => json_encode($item->options),
                'subtotal' => $subtotal ?? 0,
                'tax_rate' => $item->taxRate ?? 0,
                'tax_amount' => $taxAmount ?? 0,
                'discount_amount' => $discountAmount ?? 0,
                'total' => $total ?? 0,
            ]);
        }

        // Recalculate and update cart totals
        $this->recalculateCartTotals($cart);

        return $cart;
    }

    /**
     * Check if cart is already completed/ordered
     */
    protected function isCartCompleted($cart)
    {
        // Check if cart has an associated order
        // You might have an 'order_id' or 'status' field in your cart model
        return $cart->order_id !== null ||
            $cart->status === 'completed' ||
            $cart->status === 'ordered';
    }

    /**
     * Update session identifier
     */
    protected function updateSessionIdentifier($identifier)
    {
        $this->identifier = $identifier;
        $this->session->put($this->instance . '_identifier', $identifier);
    }
    /**
     * Recalculate cart totals and update database
     */
    protected function recalculateCartTotals($cart)
    {
        $cart->load('items');

        $subtotal = $cart->items->sum(function ($item) {
            return $item->qty * $item->price;
        });

        $taxAmount = $cart->items->sum('tax_amount');
        $discountAmount = $cart->items->sum('discount_amount');

        $total = $subtotal + $taxAmount + $cart->shipping_amount - $discountAmount;

        $cart->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
        ]);
    }

    /**
     * Restore cart from database to session
     */
    public function restore($identifier = null, $merge = false)
    {
        $identifier = $identifier ?: $this->identifier;

        $cart = CartModel::with('items')
            ->where('identifier', $identifier)
            ->where('instance', $this->currentInstance())
            ->first();

        if (!$cart) {
            return $this;
        }

        // Convert database items to session format
        $storedContent = new Collection();

        foreach ($cart->items as $item) {
            $storedContent->put($item->row_id, $this->createCartItemFromDatabase($item));
        }

        if ($merge) {
            $currentContent = $this->getContent();
            $storedContent->each(function ($item) use ($currentContent) {
                if ($currentContent->has($item->rowId)) {
                    $this->updateQty($item->rowId, $currentContent->get($item->rowId)->qty + $item->qty);
                } else {
                    $currentContent->put($item->rowId, $item);
                }
            });
            $this->session->put($this->instance, $currentContent);
        } else {
            $this->session->put($this->instance, $storedContent);
        }

        // Update session identifier
        $this->identifier = $identifier;
        $this->session->put($this->instance . '_identifier', $identifier);

        $this->events->dispatch('cart.restored');

        return $this;
    }

    /**
     * Create cart item from database model
     */
    protected function createCartItemFromDatabase($item)
    {
        return (object) [
            'rowId' => $item->row_id,
            'id' => $item->product_id,
            'name' => $item->name,
            'qty' => $item->qty,
            'price' => $item->price,
            'options' => $item->options ? json_decode($item->options, true) : [],
            'tax' => $item->tax_amount,
            'total' => $item->qty * $item->price,
            'totalTax' => $item->tax_amount,
            'isSaved' => false,
            'product' => $this->associatedModel ? $this->associatedModel::find($item->product_id) : null,
        ];
    }

    public function apiContent()
    {
        $content = $this->getContent();

        return [
            'content' => $content->values(),
            'count' => $this->itemsCount(),
            'subtotal' => $this->subtotal(),
            'tax' => $this->tax(),
            'total' => $this->total(),
            'instance' => $this->currentInstance(),
            'identifier' => $this->identifier,
        ];
    }

    /**
     * Get database cart model (useful for checkout)
     */
    public function getDatabaseCart($identifier = null)
    {
        $identifier = $identifier ?: $this->identifier;

        return CartModel::with('items.product')
            ->where('identifier', $identifier)
            ->where('instance', $this->currentInstance())
            ->first();
    }

    /**
     * Merge session cart with user's database cart (when user logs in)
     */
    public function mergeWithUser($userId)
    {
        $userCart = CartModel::with('items')
            ->where('user_id', $userId)
            ->where('instance', $this->currentInstance())
            ->active()
            ->first();

        $sessionContent = $this->getContent();

        if ($userCart) {
            // Merge session items with user cart
            foreach ($sessionContent as $cartItem) {
                $existingItem = $userCart->items()
                    ->where('row_id', $cartItem->rowId)
                    ->first();

                if ($existingItem) {
                    $existingItem->update([
                        'qty' => $existingItem->qty + $cartItem->qty
                    ]);
                } else {
                    $userCart->items()->create([
                        'row_id' => $cartItem->rowId,
                        'product_id' => $cartItem->id,
                        'name' => $cartItem->name,
                        'qty' => $cartItem->qty,
                        'price' => $cartItem->price,
                        'options' => json_encode($cartItem->options),
                    ]);
                }
            }

            // Update session identifier to user cart identifier
            $this->identifier = $userCart->identifier;
            $this->session->put($this->instance . '_identifier', $userCart->identifier);

            // Reload session from merged database cart
            $this->restore($userCart->identifier, false);
        } else {
            // Associate current session cart with user
            $this->store($this->identifier);
            CartModel::where('identifier', $this->identifier)
                ->update(['user_id' => $userId]);
        }

        return $this;
    }

    protected function getContent()
    {
        $content = $this->session->has($this->instance)
            ? $this->session->get($this->instance)
            : new Collection();

        return $content;
    }

    protected function createCartItem($id, $name, $qty, $price, array $options, $rowId)
    {
        if ($this->isMulti($id)) {
            $cartItem = $this->createMultiCartItem($id, $name, $qty, $price, $options);
        } else {
            $cartItem = $this->createSingleCartItem($id, $name, $qty, $price, $options);
        }

        $cartItem->rowId = $rowId;
        $cartItem->total = $qty * $cartItem->price;

        return $cartItem;
    }

    protected function createSingleCartItem($id, $name, $qty, $price, $options)
    {
        return (object) [
            'id' => $id,
            'name' => $name,
            'qty' => $qty,
            'price' => $price,
            'options' => $options,
            'tax' => 0,
            'isSaved' => false,
            'product' => $this->associatedModel ? $this->associatedModel::find($id) : null,
        ];
    }

    protected function createMultiCartItem($id, $name, $qty, $price, $options)
    {
        // Handle multiple items (if needed)
        return $this->createSingleCartItem($id, $name, $qty, $price, $options);
    }

    protected function generateRowId($id, $options)
    {
        ksort($options);
        return md5($id . serialize($options));
    }

    protected function hasRowId($rowId)
    {
        return $this->getContent()->has($rowId);
    }

    protected function isMulti($items)
    {
        if (!is_array($items)) {
            return false;
        }

        return is_array(head($items)) || head($items) instanceof Buyable;
    }

    protected function generateIdentifier()
    {
        return 'web_' . md5(uniqid('cart_', true));
    }
}
