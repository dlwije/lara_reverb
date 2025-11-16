<?php

namespace Modules\Wallet\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallet\Services\WalletService;

class WalletTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => app(WalletService::class)->getTransactionDescription($this),
            'direction' => $this->direction,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'currency_symble' => '',
            'payment_type' => app(WalletService::class)->getPaymentTypes($this),
            'payment_method' => $this->type,
            'status' => $this->status,
//            'ref_type' => $this->ref_type,
            'ref_id' => $this->ref_number,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'running_balance' => $this->when(isset($this->running_balance), $this->running_balance),
            'formatted_amount' => $this->formatted_amount,
            'formatted_running_balance' => isset($this->running_balance) ?
                number_format($this->running_balance, 2) . ' ' . $this->currency : null
        ];
    }
}
