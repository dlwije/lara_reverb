<?php

namespace Modules\Cart\Interfaces;
interface CartInterface
{
    public function instance($instance = null);
    public function currentInstance();
    public function add($id, $name = null, $qty = null, $price = null, array $options = []);
    public function update($rowId, $qty);
    public function remove($rowId);
    public function get($rowId);
    public function destroy();
    public function content();
    public function count();
    public function total();
    public function subtotal();
    public function tax();
    public function search($search);
    public function associate($model);
    public function store($identifier);
    public function restore($identifier);
    public function apiContent();
}
