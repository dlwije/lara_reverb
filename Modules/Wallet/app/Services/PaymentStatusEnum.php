<?php

namespace Modules\Wallet\Services;

use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Enum;

/**
 * @method static PaymentStatusEnum PENDING()
 * @method static PaymentStatusEnum COMPLETED()
 * @method static PaymentStatusEnum REFUNDING()
 * @method static PaymentStatusEnum REFUNDED()
 * @method static PaymentStatusEnum FRAUD()
 * @method static PaymentStatusEnum FAILED()
 */
class PaymentStatusEnum extends Enum
{
    public const PENDING = 'pending';

    public const COMPLETED = 'completed';

    public const REFUNDING = 'refunding';

    public const REFUNDED = 'refunded';

    public const FRAUD = 'fraud';

    public const FAILED = 'failed';

    public static $langPath = 'plugins/payment::payment.statuses';

    private $classText = [
        'default' => 'primary',
        'pending' => 'warning',
        'completed' => 'success',
        'refunding' => 'warning',
        'refunded' => 'info',
        'fraud' => 'danger',
        'failed' => 'danger'
    ];

//    public function toHtml(): HtmlString|string
//    {
//        $color = match ($this->value) {
//            self::PENDING => $this->classText[self::PENDING],
//            self::REFUNDING => $this->classText[self::REFUNDING],
//            self::COMPLETED => $this->classText[self::COMPLETED],
//            self::REFUNDED => $this->classText[self::REFUNDED],
//            self::FRAUD => $this->classText[self::FRAUD],
//            self::FAILED => $this->classText[self::FAILED],
//            default => $this->classText['default'],
//        };
//
//        return BaseHelper::renderBadge($this->label(), $color);
//    }

    public static function returnAll(): array
    {
        return [
            self::PENDING => ['label' => self::PENDING()->label(), 'value' => self::PENDING, 'class' => (new PaymentStatusEnum)->classText[self::PENDING]],
            self::COMPLETED => ['label' => self::COMPLETED()->label(), 'value' => self::COMPLETED, 'class' => (new PaymentStatusEnum)->classText[self::COMPLETED]],
            self::REFUNDING => ['label' => self::REFUNDING()->label(), 'value' => self::REFUNDING, 'class' => (new PaymentStatusEnum)->classText[self::REFUNDING]],
            self::REFUNDED => ['label' => self::REFUNDED()->label(), 'value' => self::REFUNDED, 'class' => (new PaymentStatusEnum)->classText[self::REFUNDED]],
            self::FRAUD => ['label' => self::FRAUD()->label(), 'value' => self::FRAUD, 'class' => (new PaymentStatusEnum)->classText[self::FRAUD]],
            self::FAILED => ['label' => self::FAILED()->label(), 'value' => self::FAILED, 'class' => (new PaymentStatusEnum)->classText[self::FAILED]]
        ];
    }
}
