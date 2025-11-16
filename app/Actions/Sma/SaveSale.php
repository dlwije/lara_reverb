<?php

namespace App\Actions\Sma;

use App\Models\Sma\Order\Sale;
use Illuminate\Support\Facades\DB;

class SaveSale
{
    /**
     * Save sales with relationships
     *
     * @param  array<string, string>  $input
     * @param  Sale  $input
     */
    public function execute(array $data, Sale $sale = new Sale): Sale
    {
        // logger()->info('Sale form data: ', $data);

        $oldSale = null;
        if ($sale?->id) {
            $oldSale = $sale->load([
                'store', 'customer', 'items.product', 'items.variations',
            ])->replicateQuietly();
            $oldSale->id = $sale->id;
        }

        DB::transaction(function () use ($data, &$sale) {
            $items = $data['items'];
            $taxes = $data['taxes'] ?? [];
            $payments = $data['payments'] ?? [];
            $attachments = $data['attachments'] ?? [];
            unset($data['attachments'], $data['items'], $data['taxes'], $data['payments']);

            $sale->fill($data)->save();
            $sale->taxes()->sync($taxes);

            foreach ($items as $item) {
                $taxes = $item['taxes'] ?? [];
                $variations = $item['variations'] ?? null;
                unset($item['taxes'],$item['variations'], $item['old_quantity'], $item['tax_included']);
                $item['quantity'] = $variations ? collect($variations)->sum('quantity') : $item['quantity'];

                if (($item['id'] ?? null) && $ii = $sale->items->where('id', $item['id'])->first()) {
                    $ii->update($item);
                    if ($variations ?? null) {
                        $variationIds = [];
                        $syncVariations = [];
                        foreach ($variations as $variation) {
                            $id = $variation['id'];
                            $variationIds[] = $id;
                            unset($variation['id'], $variation['old_quantity'], $variation['tax_included']);
                            $syncVariations[$id] = $variation;
                        }
                        $ii->variations()->sync($syncVariations);
                    }
                    $ii->taxes()->sync($taxes);
                } else {
                    $ii = $sale->items()->create($item);
                    if ($variations) {
                        $syncVariations = [];
                        foreach ($variations as $variation) {
                            $id = $variation['id'];
                            unset($variation['id'], $variation['old_quantity'], $variation['tax_included']);
                            $syncVariations[$id] = $variation;
                        }
                        $ii->variations()->sync($syncVariations);
                    }
                    $ii->taxes()->sync($taxes);
                }
            }

            $sale->saveAttachments($attachments);

            $sale->order?->delete();
            if (! empty($payments)) {
                foreach ($payments as $payment) {
                    if (($payment['amount'] ?? null) && ($payment['method'] ?? null)) {
                        $payment['date'] ??= now()->toDateString();
                        $payment['payment_for'] = 'Customer';
                        $payment['sale_id'] = $sale->id;
                        $payment['customer_id'] = $sale->customer_id;
                        $sale->customer->payments()->create($payment);
                    }
                }
            }
        });

        $sale->refresh()->loadMissing([
            'store', 'customer', 'items.product', 'items.variations',
        ]);
//        event(new SaleEvent($sale, $oldSale));
//
//        if ($oldSale) {
//            ReportSaleUpdate::dispatch($sale, $oldSale);
//        } else {
//            ReportNewSale::dispatch($sale);
//        }

        return $sale;
    }
}
