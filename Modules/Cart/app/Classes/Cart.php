<?php

namespace Modules\Cart\Classes;

use Carbon\Carbon;
use Closure;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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

    public function __construct(SessionManager $session, $events = null, $instance = null)
    {
        $this->session = $session;
        $this->events = $events;
        $this->instance($instance ?? self::DEFAULT_INSTANCE);
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

    public function store($identifier)
    {
        $content = $this->getContent();

        if ($content->isEmpty()) {
            return;
        }

        $cart = CartModel::updateOrCreate(
            [
                'identifier' => $identifier,
                'instance' => $this->currentInstance(),
            ],
            [
                'content' => serialize($content),
                'user_id' => Auth::id(),
            ]
        );

        // Store individual items for better querying
        foreach ($content as $rowId => $item) {
            CartItem::updateOrCreate(
                [
                    'cart_id' => $cart->id,
                    'row_id' => $rowId,
                ],
                [
                    'product_id' => $item->id,
                    'name' => $item->name,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'options' => json_encode($item->options),
                ]
            );
        }

        return $cart;
    }

    public function restore($identifier, $merge = false)
    {
        $cart = CartModel::where('identifier', $identifier)
            ->where('instance', $this->currentInstance())
            ->first();

        if (!$cart) {
            return;
        }

        $storedContent = unserialize($cart->content);

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

        $this->events->dispatch('cart.restored');

        return $this;
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
        ];
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
}
