<?php

namespace App\Controller;

use App\Entity\{Customer, CustomerPayment, FuelShiftReading, Stations};
use App\Service\CustomerAccountService;
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/customers')]
final class CustomerController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->requireCustomerAccess()) return $denied;
        $station = $em->getRepository(Stations::class)->find((int) $request->query->get('station'));
        if (!$station || !$access->canAccessStation($station)) return $access->denyStation();
        $customers = $em->getRepository(Customer::class)->findBy(['station' => $station], ['name' => 'ASC']);

        $billedByCustomer = [];
        $sales = $em->getRepository(FuelShiftReading::class)->findBy(['station' => $station], ['workDate' => 'DESC', 'id' => 'DESC']);
        foreach ($sales as $reading) {
            $customer = $reading->getCustomer();
            if (!$customer) continue;
            $customerId = $customer->getId();
            $billedByCustomer[$customerId] = ($billedByCustomer[$customerId] ?? 0.0) + (float) $reading->getTotalAmount();
        }

        $paidByCustomer = [];
        $payments = $em->getRepository(CustomerPayment::class)->findBy(['station' => $station], ['paymentDate' => 'DESC', 'id' => 'DESC']);
        foreach ($payments as $payment) {
            $customer = $payment->getCustomer();
            if (!$customer) continue;
            $customerId = $customer->getId();
            $paidByCustomer[$customerId] = ($paidByCustomer[$customerId] ?? 0.0) + (float) $payment->getAmount();
        }

        return $this->json([
            'customers' => array_map(fn(Customer $customer) => $this->row($customer, $billedByCustomer[$customer->getId()] ?? 0.0, $paidByCustomer[$customer->getId()] ?? 0.0), $customers),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_GERANT)) return $denied;
        $data = $request->toArray(); $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        if (!$station || !$access->canAccessStation($station)) return $access->denyStation();
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') return $this->json(['message' => 'Le nom du client est obligatoire'], 422);
        $customer = (new Customer())->setStation($station)->setName($name)->setCode($this->nullable($data['code'] ?? null))->setContactPerson($this->nullable($data['contactPerson'] ?? null))->setPhone($this->nullable($data['phone'] ?? null))->setEmail($this->nullable($data['email'] ?? null))->setAddress($this->nullable($data['address'] ?? null))->setCreatedAt(new \DateTimeImmutable());
        $em->persist($customer); $em->flush();
        return $this->json($this->row($customer), 201);
    }

    #[Route('/payments', methods: ['POST'])]
    public function payment(Request $request, EntityManagerInterface $em, UserAccessService $access, CustomerAccountService $accountService): JsonResponse
    {
        if ($denied = $access->requireCustomerAccess()) return $denied;
        $data = $request->toArray();
        $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        $customer = $em->getRepository(Customer::class)->find((int) ($data['customerId'] ?? 0));
        if (!$station || !$customer || !$access->canAccessStation($station) || $customer->getStation()?->getId() !== $station->getId()) {
            return $this->json(['message' => 'Client ou station invalide'], 422);
        }

        $amount = max(0.0, (float) ($data['amount'] ?? 0));
        if ($amount < 0) return $this->json(['message' => 'Le montant du paiement ne peut pas être négatif'], 422);

        $billed = 0.0;
        $sales = $em->getRepository(FuelShiftReading::class)->findBy(['station' => $station, 'customer' => $customer], ['workDate' => 'DESC', 'id' => 'DESC']);
        foreach ($sales as $reading) $billed += (float) $reading->getTotalAmount();

        $paidBefore = 0.0;
        $payments = $em->getRepository(CustomerPayment::class)->findBy(['station' => $station, 'customer' => $customer], ['paymentDate' => 'DESC', 'id' => 'DESC']);
        foreach ($payments as $entry) $paidBefore += (float) $entry->getAmount();

        if ($amount > $billed - $paidBefore + 0.01) {
            return $this->json(['message' => 'Le paiement dépasse le solde du client'], 422);
        }

        $snapshot = $accountService->paymentSnapshot($billed, $paidBefore, $amount);

        $payment = (new CustomerPayment())
            ->setStation($station)
            ->setCustomer($customer)
            ->setPerformedBy($access->currentUser())
            ->setPaymentDate(new \DateTimeImmutable((string) ($data['date'] ?? 'today')))
            ->setAmount((string) $amount)
            ->setPreviousBalance((string) $snapshot['previousBalance'])
            ->setRemainingBalance((string) $snapshot['remainingBalance'])
            ->setPaymentMethod((string) ($data['method'] ?? 'CASH'))
            ->setReference(trim((string) ($data['reference'] ?? '')) ?: null)
            ->setNote(trim((string) ($data['note'] ?? '')) ?: null)
            ->setCreatedAt(new \DateTimeImmutable());

        $em->persist($payment);
        $em->flush();
        return $this->json(['id' => $payment->getId()], 201);
    }

    #[Route('/payment-history', methods: ['GET'])]
    public function paymentHistory(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->requireCustomerAccess()) return $denied;
        $stationId = (int) $request->query->get('station');
        $station = $em->getRepository(Stations::class)->find($stationId);
        if (!$station || !$access->canAccessStation($station)) return $access->denyStation();

        $from = trim((string) $request->query->get('from', ''));
        $to = trim((string) $request->query->get('to', ''));
        $customerName = trim((string) $request->query->get('customer', ''));

        $payments = $em->getRepository(CustomerPayment::class)->findBy(['station' => $station], ['paymentDate' => 'DESC', 'id' => 'DESC']);
        $filtered = array_values(array_filter($payments, static function (CustomerPayment $payment) use ($from, $to, $customerName): bool {
            $date = $payment->getPaymentDate()?->format('Y-m-d');
            if ($from !== '' && $date !== null && $date < $from) return false;
            if ($to !== '' && $date !== null && $date > $to) return false;
            if ($customerName !== '') {
                $name = strtolower((string) $payment->getCustomer()?->getName());
                if (stripos($name, strtolower($customerName)) === false) return false;
            }
            return true;
        }));

        $rows = [];
        foreach ($filtered as $payment) {
            $customer = $payment->getCustomer();
            if (!$customer) continue;
            $customerId = $customer->getId();
            $paymentDate = $payment->getPaymentDate();

            $sales = $em->getRepository(FuelShiftReading::class)->findBy(['station' => $station, 'customer' => $customer], ['workDate' => 'ASC', 'id' => 'ASC']);
            $billedBefore = 0.0;
            foreach ($sales as $sale) {
                $saleDate = $sale->getWorkDate();
                if ($saleDate && $saleDate <= $paymentDate) {
                    $billedBefore += (float) $sale->getTotalAmount();
                }
            }

            $previousPayments = $em->getRepository(CustomerPayment::class)->findBy(['station' => $station, 'customer' => $customer], ['paymentDate' => 'ASC', 'id' => 'ASC']);
            $paidBefore = 0.0;
            foreach ($previousPayments as $entry) {
                $entryDate = $entry->getPaymentDate();
                if ($entryDate && $entryDate < $paymentDate) {
                    $paidBefore += (float) $entry->getAmount();
                } elseif ($entryDate && $entryDate == $paymentDate && $entry->getId() < $payment->getId()) {
                    $paidBefore += (float) $entry->getAmount();
                }
            }

            $amount = (float) $payment->getAmount();
            $previousBalance = (float) $payment->getPreviousBalance();
            $remainingToPay = (float) $payment->getRemainingBalance();
            if ($previousBalance <= 0.0 && $remainingToPay <= 0.0) {
                $previousBalance = max(0.0, $billedBefore - $paidBefore);
                $remainingToPay = max(0.0, $previousBalance - $amount);
            }

            $rows[] = [
                'id' => $payment->getId(),
                'customerId' => $customerId,
                'customer' => $customer->getName(),
                'date' => $paymentDate?->format('Y-m-d'),
                'performedBy' => trim((string) (($payment->getPerformedBy()?->getFirstName() ?? '') . ' ' . ($payment->getPerformedBy()?->getLastName() ?? ''))) ?: '—',
                'amount' => $amount,
                'method' => $payment->getPaymentMethod(),
                'reference' => $payment->getReference(),
                'note' => $payment->getNote(),
                'previousBalance' => round($previousBalance, 2),
                'remainingToPay' => round($remainingToPay, 2),
            ];
        }

        return $this->json(['payments' => $rows]);
    }

    #[Route('/{id}', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(Customer $customer, Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->requireCustomerAccess()) return $denied;
        if (!$access->canAccessStation($customer->getStation())) return $access->denyStation();
        $data = $request->toArray(); $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') return $this->json(['message' => 'Le nom du client est obligatoire'], 422);
        $customer->setName($name)->setCode($this->nullable($data['code'] ?? null))->setContactPerson($this->nullable($data['contactPerson'] ?? null))->setPhone($this->nullable($data['phone'] ?? null))->setEmail($this->nullable($data['email'] ?? null))->setAddress($this->nullable($data['address'] ?? null));
        $em->flush(); return $this->json($this->row($customer));
    }

    #[Route('/{id}/deactivate', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function deactivate(Customer $customer, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->requireCustomerAccess()) return $denied;
        if (!$access->canAccessStation($customer->getStation())) return $access->denyStation();
        $customer->setIsActive(false); $em->flush(); return $this->json($this->row($customer));
    }

    private function nullable(mixed $value): ?string { $value = trim((string) $value); return $value ?: null; }
    private function row(Customer $customer, float $billed = 0.0, float $paid = 0.0): array { $balance = $billed - $paid; return ['id' => $customer->getId(), 'code' => $customer->getCode(), 'name' => $customer->getName(), 'contactPerson' => $customer->getContactPerson(), 'phone' => $customer->getPhone(), 'email' => $customer->getEmail(), 'address' => $customer->getAddress(), 'active' => $customer->isActive(), 'billed' => round($billed, 2), 'paid' => round($paid, 2), 'balance' => round($balance, 2)]; }
}
