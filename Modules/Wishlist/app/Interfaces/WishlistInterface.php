<?php

namespace Modules\Wishlist\Interfaces;

interface WishlistInterface
{
    public function instance($instance = null);
    public function currentInstance();
    public function add($id, $name = null, $price = null, array $options = []);
    public function update($rowId, array $options = []);
    public function remove($rowId);
    public function get($rowId);
    public function destroy();
    public function content();
    public function count();
    public function search($search);
    public function associate($model);
    public function store($identifier);
    public function restore($identifier);
    public function apiContent();
    public function moveToCart($rowId, $qty = 1);
    public function has($rowId);
    public function exists($productId);
}
