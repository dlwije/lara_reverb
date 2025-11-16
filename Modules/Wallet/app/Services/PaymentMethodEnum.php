<?php

namespace Modules\Wallet\Services;

use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Enum;
use Modules\Telr\Services\TelrPaymentService;

/**
 * @method static PaymentStatusEnum PENDING()
 * @method static PaymentStatusEnum COMPLETED()
 * @method static PaymentStatusEnum REFUNDING()
 * @method static PaymentStatusEnum REFUNDED()
 * @method static PaymentStatusEnum FRAUD()
 * @method static PaymentStatusEnum FAILED()
 */
class PaymentMethodEnum extends Enum
{
    public const COD = 'cod';
    public const BANK_TRANSFER = 'bank_transfer';
    public const APPLE_PAY = 'apple_pay';
    public const WALLET = 'wallet';

    public static $langPath = 'plugins/payment::payment.methods';

    public function getServiceClass(): ?string
    {
        if ($this->value == TELR_PAYMENT_METHOD_NAME) {
            $data = TelrPaymentService::class;
        }

        return $data;
    }

    public static function values(): array
    {
        return [
            self::COD,
            self::BANK_TRANSFER,
            self::APPLE_PAY,
            self::WALLET,
        ];
    }
}
