<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\CustomerAccountService;
use PHPUnit\Framework\TestCase;

final class CustomerAccountServiceTest extends TestCase
{
    public function testBuildsCustomerAccountFromSalesAndPayments(): void
    {
        $service = new CustomerAccountService();

        $customer = ['id' => 7, 'name' => 'Client Test', 'code' => 'CT', 'contactPerson' => 'Alice', 'phone' => '0320000000', 'email' => 'alice@example.com', 'address' => 'Tana', 'active' => true];
        $sales = [
            ['customerId' => 7, 'amount' => 5000],
            ['customerId' => 7, 'amount' => 1500],
        ];
        $payments = [
            ['customerId' => 7, 'amount' => 2000],
        ];

        $this->assertSame([
            'id' => 7,
            'code' => 'CT',
            'name' => 'Client Test',
            'contactPerson' => 'Alice',
            'phone' => '0320000000',
            'email' => 'alice@example.com',
            'address' => 'Tana',
            'active' => true,
            'billed' => 6500,
            'paid' => 2000,
            'balance' => 4500,
        ], $service->summarize($customer, $sales, $payments));
    }

    public function testPaymentSnapshotReflectsBalanceBeforeAndAfterThePayment(): void
    {
        $service = new CustomerAccountService();

        $this->assertSame([
            'previousBalance' => 20000.0,
            'remainingBalance' => 15000.0,
        ], $service->paymentSnapshot(25000, 5000, 5000));
    }
}
