<?php

declare(strict_types=1);

namespace App\Tests;

use App\Controller\FuelPaymentController;
use PHPUnit\Framework\TestCase;

final class FuelPaymentModeFilterTest extends TestCase
{
    public function testCustomerVoucherIsOnlyAvailableForCreditSales(): void
    {
        $methods = [
            ['code' => 'CASH'],
            ['code' => 'CLIENT_VOUCHER'],
            ['code' => 'CHEQUE'],
        ];

        $this->assertSame(['CASH', 'CHEQUE'], FuelPaymentController::filterMethodsForMode($methods, 'simple'));
        $this->assertSame(['CLIENT_VOUCHER'], FuelPaymentController::filterMethodsForMode($methods, 'credit'));
    }
}
