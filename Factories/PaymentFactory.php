<?php

declare(strict_types=1);

/**
 * Contains the PaymentFactory class.
 *
 * @copyright   Copyright (c) 2020 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2020-12-30
 *
 */

namespace App\Factories\Payment;

use Vanilo\Contracts\Payable;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Contracts\PaymentMethod;
use Vanilo\Payment\Events\PaymentCreated;
use Vanilo\Payment\Models\PaymentProxy;
use Vanilo\Payment\Models\PaymentStatusProxy;

class PaymentFactory
{
	public static function createFromPayable(
		Payable $payable,
		PaymentMethod $paymentMethod,
		array $extraData = []
	): Payment {
		$payment = PaymentProxy::create([
			'amount' => $paymentMethod->isCardPayment() ? $payable->card_used_balance : $payable->getAmount(),
			'currency' => $payable->getCurrency(),
			'payable_type' => $payable->getPayableType(),
			'payable_id' => $payable->getPayableId(),
			'payment_method_id' => $paymentMethod->id,
			'data' => $extraData,
			'status' => $paymentMethod->isCardPayment() ? PaymentStatusProxy::PAID() : PaymentStatusProxy::PENDING(),
		]);

		event(new PaymentCreated($payment));

		return $payment;
	}
}
