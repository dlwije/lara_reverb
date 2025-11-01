<?php

namespace Modules\Wishlist\Classes;

use Closure;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use Modules\Wishlist\Interfaces\WishlistInterface;
use Modules\Wishlist\Models\Wishlist as WishlistModel;

class Wishlist implements WishlistInterface
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

        $this->instance = sprintf('%s.%s', 'wishlist', $instance);

        return $this;
    }

    public function currentInstance()
    {
        return str_replace('wishlist.', '', $this->instance);
    }

    public function add($id, $name = null, $price = null, array $options = [])
    {
        if (is_array($id)) {
            $wishlistItem = $this->addArray($id);
        } else {
            $wishlistItem = $this->addItem($id, $name, $price, $options);
        }

        $this->events->dispatch('wishlist.added', $wishlistItem);

        return $wishlistItem;
    }

    protected function addArray(array $items)
    {
        $wishlistItems = collect();

        foreach ($items as $item) {
            $wishlistItems->push($this->addItem(
                $item['id'],
                $item['name'],
                $item['price'] ?? 0,
                $item['options'] ?? []
            ));
        }

        return $wishlistItems;
    }

    protected function addItem($id, $name, $price, array $options = [])
    {
        $rowId = $this->generateRowId($id, $options);

        if ($this->hasRowId($rowId)) {
            return $this->get($rowId);
        }

        $wishlistItem = $this->createWishlistItem($id, $name, $price, $options, $rowId);

        $content = $this->getContent();
        $content->put($rowId, $wishlistItem);

        $this->session->put($this->instance, $content);

        return $wishlistItem;
    }

    public function update($rowId, array $options = [])
    {
        $wishlistItem = $this->get($rowId);

        // Update options
        $wishlistItem->options = array_merge($wishlistItem->options, $options);

        $content = $this->getContent();
        $content->put($rowId, $wishlistItem);

        $this->session->put($this->instance, $content);

        $this->events->dispatch('wishlist.updated', $wishlistItem);

        return $wishlistItem;
    }

    public function remove($rowId)
    {
        $wishlistItem = $this->get($rowId);

        $content = $this->getContent();
        $content->forget($rowId);

        $this->session->put($this->instance, $content);

        $this->events->dispatch('wishlist.removed', $wishlistItem);

        return $this;
    }

    public function get($rowId)
    {
        $content = $this->getContent();

        if (!$content->has($rowId)) {
            throw new \InvalidArgumentException("The wishlist does not contain rowId {$rowId}.");
        }

        return $content->get($rowId);
    }

    public function has($rowId)
    {
        return $this->getContent()->has($rowId);
    }

    public function exists($productId)
    {
        return $this->getContent()->contains(function ($item) use ($productId) {
            return $item->id == $productId;
        });
    }

    public function destroy()
    {
        $this->session->remove($this->instance);
        $this->events->dispatch('wishlist.destroyed');
    }

    public function content()
    {
        return $this->getContent();
    }

    public function count()
    {
        return $this->getContent()->count();
    }

    public function search($search)
    {
        if ($search instanceof Closure) {
            return $this->getContent()->filter($search);
        }

        return $this->getContent()->filter(function ($wishlistItem) use ($search) {
            return stripos($wishlistItem->id, $search) !== false ||
                stripos($wishlistItem->name, $search) !== false;
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

        $wishlist = WishlistModel::updateOrCreate(
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
            WishlistItem::updateOrCreate(
                [
                    'wishlist_id' => $wishlist->id,
                    'row_id' => $rowId,
                ],
                [
                    'product_id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'options' => json_encode($item->options),
                ]
            );
        }

        return $wishlist;
    }

    public function restore($identifier, $merge = false)
    {
        $wishlist = WishlistModel::where('identifier', $identifier)
            ->where('instance', $this->currentInstance())
            ->first();

        if (!$wishlist) {
            return;
        }

        $storedContent = unserialize($wishlist->content);

        if ($merge) {
            $currentContent = $this->getContent();
            $storedContent->each(function ($item) use ($currentContent) {
                if (!$currentContent->has($item->rowId)) {
                    $currentContent->put($item->rowId, $item);
                }
            });
            $this->session->put($this->instance, $currentContent);
        } else {
            $this->session->put($this->instance, $storedContent);
        }

        $this->events->dispatch('wishlist.restored');

        return $this;
    }

    public function apiContent()
    {
        $content = $this->getContent();

        return [
            'content' => $content->values(),
            'count' => $this->count(),
            'instance' => $this->currentInstance(),
        ];
    }

    public function moveToCart($rowId, $qty = 1)
    {
        $wishlistItem = $this->get($rowId);

        // Add to cart
        $cartItem = app('cart')->instance('cart')->add(
            $wishlistItem->id,
            $wishlistItem->name,
            $qty,
            $wishlistItem->price,
            $wishlistItem->options
        );

        // Remove from wishlist
        $this->remove($rowId);

        $this->events->dispatch('wishlist.moved_to_cart', [
            'wishlist_item' => $wishlistItem,
            'cart_item' => $cartItem
        ]);

        return $cartItem;
    }

    protected function getContent()
    {
        $content = $this->session->has($this->instance)
            ? $this->session->get($this->instance)
            : new Collection();

        return $content;
    }

    protected function createWishlistItem($id, $name, $price, array $options, $rowId)
    {
        return (object) [
            'rowId' => $rowId,
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'options' => $options,
            'product' => $this->associatedModel ? $this->associatedModel::find($id) : null,
            'added_at' => now(),
        ];
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
}
