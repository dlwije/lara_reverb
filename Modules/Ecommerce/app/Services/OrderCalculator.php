<?php

namespace Modules\Ecommerce\Services;

use App\Models\Sma\Setting\Tax;
use Modules\Product\Models\Product;

class OrderCalculator
{
    // Calculate order and prepare data
    public static function calculate(array $data, $calc_on = 'price')
    {
        $coupon = $data['coupon'] ?? null;

        $itemIds = collect($data['items'])->pluck('product_id');
        $products = Product::with('unit.subunits')
            ->whereIn('id', $itemIds)->get(['id', 'tax_included', 'unit_id']);

        $items = collect($data['items'])->transform(function ($item) use ($products, $calc_on, $coupon) {
            $product = $products->where('id', $item['product_id'])->first();

            $item['product_unit'] = $product->unit;
            $item['tax_included'] = $product->tax_included;

            if ($coupon) {
                $item['discount'] = $coupon->discount . '%';
            }
            $item = self::calculateTotal($item, $calc_on);

            return $item;
        });

        $data['total'] = format_decimal($items->sum('total'));
        $data['total_items'] = format_decimal($items->count());
        $data['subtotal'] = format_decimal($items->sum('subtotal'));
        $data['total_quantity'] = format_decimal_qty($items->sum('base_quantity'));
        $data['total_tax_amount'] = format_decimal($items->sum('total_tax_amount'));
        $data['total_discount_amount'] = format_decimal($items->sum('total_discount_amount'));
        $data['grand_total'] = format_decimal($items->sum('total')) + ($data['shipping'] ?? 0);
        $data['items'] = $items->all();

        if ($calc_on == 'price') {
            $data['total_cost'] = $items->sum('total_cost');
        }

        if ($coupon) {
            $data['shop_coupon_id'] = $coupon->id;
        }

        return $data;
    }

    // Calculate Totals
    public static function calculateTotal(array $item, $calc_on = 'price')
    {
        if ($item['variations'] ??= null) {
            foreach ($item['variations'] as &$variation) {
                $variation = self::calculateDiscount($variation, $calc_on);
                $variation['tax_included'] = $item['tax_included'];
                $variation['taxes'] = $variation['taxes'] ?? $item['taxes'] ?? [];
                $taxes = Tax::whereIn('id', $variation['taxes'])->get();
                $variation = self::calculateTaxes($taxes, $variation, $calc_on);

                $variation['unit_' . $calc_on] = format_decimal($variation[$calc_on]) - format_decimal($variation['discount_amount'] ?? 0) + format_decimal($item['tax_included'] ? 0 : $variation['tax_amount']);
                $variation['net_' . $calc_on] = format_decimal($variation[$calc_on]) - format_decimal($variation['discount_amount'] ?? 0) - format_decimal($item['tax_included'] ? $variation['tax_amount'] : 0);
                $variation['total'] = format_decimal(($variation['unit_' . $calc_on]) * $variation['quantity']);
                $variation['subtotal'] = format_decimal(($variation['net_' . $calc_on]) * $variation['quantity']);
                $variation['base_quantity'] = format_decimal_qty(($variation['unit_id'] ?? null) ? convert_to_base_unit($item['product_unit'], $variation['unit_id'], $variation['quantity']) : $variation['quantity'], 4);
                if ($calc_on == 'price') {
                    $item['total_cost'] = format_decimal(($variation['cost'] ?? $item['cost']) * $variation['base_quantity']);
                }
            }

            $variations = collect($item['variations']);
            $item[$calc_on] = format_decimal($variations->sum($calc_on));
            $item['net_' . $calc_on] = format_decimal($variations->sum('net_' . $calc_on));
            $item['unit_' . $calc_on] = format_decimal($variations->sum('unit_' . $calc_on));
            $item['discount_amount'] = format_decimal($variations->sum('discount_amount'));
            $item['tax_amount'] = format_decimal($variations->sum('tax_amount') / $variations->count());
            $item['total_tax_amount'] = format_decimal($variations->sum('total_tax_amount'));
            $item['subtotal'] = format_decimal($variations->sum('subtotal'));
            $item['total'] = format_decimal($variations->sum('total'));
            $item['base_quantity'] = format_decimal($variations->sum('base_quantity'));
            if ($calc_on == 'price') {
                $item['total_cost'] = format_decimal($variations->sum('total_cost'));
            }
        } else {
            $item = self::calculateDiscount($item, $calc_on);
            $taxes = Tax::whereIn('id', $item['taxes'] ?? [])->get();
            $item = self::calculateTaxes($taxes, $item, $calc_on);

            $item['unit_' . $calc_on] = format_decimal($item[$calc_on]) - format_decimal($item['discount_amount'] ?? 0) + format_decimal($item['tax_included'] ? 0 : $item['tax_amount']);
            $item['net_' . $calc_on] = format_decimal($item[$calc_on]) - format_decimal($item['discount_amount'] ?? 0) - format_decimal($item['tax_included'] ? $item['tax_amount'] : 0);
            $item['total'] = format_decimal(($item['unit_' . $calc_on]) * $item['quantity']);
            $item['subtotal'] = format_decimal(($item['net_' . $calc_on]) * $item['quantity']);
            $item['base_quantity'] = format_decimal_qty(($item['unit_id'] ?? null) ? convert_to_base_unit($item['product_unit'], $item['unit_id'], $item['quantity']) : $item['quantity'], 4);
            if ($calc_on == 'price') {
                $item['total_cost'] = format_decimal($item['cost'] * $item['base_quantity']);
            }
        }

        unset($item['product_unit']);

        return $item;
    }

    // Calculate Discount
    public static function calculateDiscount($item, $calc_on = 'price')
    {
        if ($item['discount'] ??= null) {
            if (str($item['discount'])->contains('%')) {
                $discount_percentage = (float) str($item['discount'])->before('%')->toString();
                $item['discount_amount'] = format_decimal(($item[$calc_on] * $discount_percentage) / 100);
            } else {
                $item['discount_amount'] = format_decimal($item['discount']);
            }
            $item['total_discount_amount'] = format_decimal($item['discount_amount'] * $item['quantity']);
        }

        return $item;
    }

    // Calculate Taxes
    public static function calculateTaxes($taxes, array $item, $calc_on = 'price')
    {
        $item['tax_amount'] = 0;
        $item['total_tax_amount'] = 0;
        $amount = $item[$calc_on] - ($item['discount_amount'] ?? 0);

        if (! empty($taxes)) {
            foreach ($taxes as $t) {
                if ($item['tax_included']) {
                    $item['tax_amount'] += self::calculateInclusiveTax($amount, $t);
                } else {
                    $item['tax_amount'] += self::calculateExclusiveTax($amount, $t);
                }
            }
            $item['total_tax_amount'] += format_decimal($item['tax_amount'] * $item['quantity']);
        }

        return $item;
    }

    public static function calculateInclusiveTax($amount, Tax $tax)
    {
        $inclusive_tax_formula = get_settings('inclusive_tax_formula');
        if ($inclusive_tax_formula == 'inclusive') {
            return format_decimal(($amount * $tax->rate) / (100 + $tax->rate));
        }

        return calculateExclusiveTax($amount, $tax);
    }

    public static function calculateExclusiveTax($amount, Tax $tax)
    {
        return format_decimal(($amount * $tax->rate) / 100);
    }
}
