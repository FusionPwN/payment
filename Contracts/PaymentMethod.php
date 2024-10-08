<?php

declare(strict_types=1);

/**
 * Contains the PaymentMethod interface.
 *
 * @copyright   Copyright (c) 2019 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2019-12-17
 *
 */

namespace Vanilo\Payment\Contracts;

interface PaymentMethod
{
    public const DEFAULT_TIMEOUT = 600;

    /**
     * Time in seconds after an initiated payment request is being considered as timed out
     *
     * @return int
     */
    public function getTimeout(): int;

    public function getGateway(): PaymentGateway;

    public function getConfiguration(): array;

    public function isEnabled(): bool;

    public function getName(): string;
}
