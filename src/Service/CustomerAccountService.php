<?php

namespace App\Service;

final class CustomerAccountService
{
    public function summarize(array $customer, array $sales, array $payments): array
    {
        $customerId = (int) ($customer['id'] ?? 0);

        $billed = 0.0;
        foreach ($sales as $sale) {
            if ((int) ($sale['customerId'] ?? 0) !== $customerId) {
                continue;
            }
            $billed += (float) ($sale['amount'] ?? 0);
        }

        $paid = 0.0;
        foreach ($payments as $payment) {
            if ((int) ($payment['customerId'] ?? 0) !== $customerId) {
                continue;
            }
            $paid += (float) ($payment['amount'] ?? 0);
        }

        return [
            'id' => $customerId,
            'code' => $customer['code'] ?? null,
            'name' => $customer['name'] ?? '',
            'contactPerson' => $customer['contactPerson'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'email' => $customer['email'] ?? null,
            'address' => $customer['address'] ?? null,
            'active' => (bool) ($customer['active'] ?? true),
            'billed' => (int) round($billed),
            'paid' => (int) round($paid),
            'balance' => (int) round($billed - $paid),
        ];
    }

    public function paymentSnapshot(float $billedTotal, float $paidBefore, float $paymentAmount): array
    {
        $previousBalance = max(0.0, $billedTotal - $paidBefore);
        $remainingBalance = max(0.0, $previousBalance - $paymentAmount);

        return [
            'previousBalance' => round($previousBalance, 2),
            'remainingBalance' => round($remainingBalance, 2),
        ];
    }
}
