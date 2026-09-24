<?php

namespace App\Controller;

use App\Entity\FuelNozzle;
use App\Entity\FuelPaymentMethod;
use App\Entity\FuelShiftReading;
use App\Entity\Customer;
use App\Entity\PumpAttendant;
use App\Entity\Stations;
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fuel')]
final class FuelPaymentController extends AbstractController
{
    private const DEFAULT_METHODS = [
        'CASH' => 'Espèces', 'CHEQUE' => 'Chèque', 'TPE' => 'Carte TPE',
        'FANILO' => 'Carte FANILO', 'VISA' => 'Carte Visa', 'FMS' => 'FMS',
        'CLIENT_VOUCHER' => 'Bons clients', 'STATION_OPERATION' => 'Fonctionnement station',
    ];

    public static function filterMethodsForMode(array $methods, string $mode): array
    {
        $mode = strtolower($mode);
        $codes = array_values(array_filter(array_map(static fn (array $method): ?string => ($method['code'] ?? '') ?: null, $methods), static fn (?string $code): bool => $code !== null));

        if ($mode === 'credit') {
            return array_values(array_filter($codes, static fn (string $code): bool => $code === 'CLIENT_VOUCHER'));
        }

        return array_values(array_filter($codes, static fn (string $code): bool => !in_array($code, ['CLIENT_VOUCHER'], true)));
    }

    #[Route('/payment-methods', methods: ['GET'])]
    public function methods(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $station = $em->getRepository(Stations::class)->find((int) $request->query->get('station'));
        if (!$station) return $this->json(['message' => 'Station invalide'], 422);
        if (!$access->canAccessStation($station)) return $access->denyStation();
        $items = $em->getRepository(FuelPaymentMethod::class)->findBy(['station' => $station], ['name' => 'ASC']);
        if (!$items) {
            foreach (self::DEFAULT_METHODS as $code => $name) {
                $roles = $code === 'CASH' ? [UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT] : [UserAccessService::ROLE_GERANT];
                $item = (new FuelPaymentMethod())->setStation($station)->setCode($code)->setName($name)->setAllowedRoles($roles)->setSupplierDeduction(in_array($code, ['FANILO', 'TPE', 'VISA', 'FMS'], true))->setCreatedAt(new \DateTimeImmutable());
                $em->persist($item);
                $items[] = $item;
            }
            $em->flush();
        }
        $manage = $request->query->getBoolean('manage');
        $mode = strtolower((string) $request->query->get('mode', 'simple'));
        if ($manage && !$access->isSuperAdmin() && !$access->canEditFuelUnitPrice()) return $this->json(['message' => 'Accès refusé pour ce rôle.'], 403);
        if (!$manage) $items = array_values(array_filter($items, fn(FuelPaymentMethod $item) => $this->canUseMethod($item, $access)));
        $methods = array_map(fn(FuelPaymentMethod $x) => $this->methodRow($x), $items);
        $methods = self::filterMethodsForMode($methods, $mode) === [] ? $methods : array_values(array_filter($methods, fn(array $method): bool => in_array((string) ($method['code'] ?? ''), self::filterMethodsForMode($methods, $mode), true)));
        return $this->json(['methods' => $methods]);
    }

    #[Route('/payment-methods', methods: ['POST'])]
    public function createMethod(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_GERANT)) return $denied;
        $data = $request->toArray();
        $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        $name = trim((string) ($data['name'] ?? ''));
        if (!$station || $name === '') return $this->json(['message' => 'Station et libellé obligatoires'], 422);
        if (!$access->canAccessStation($station)) return $access->denyStation();
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $code = preg_replace('/[^A-Z0-9_]+/', '_', $code ?: $name) ?: 'MODE';
        if ($em->getRepository(FuelPaymentMethod::class)->findOneBy(['station' => $station, 'code' => $code])) return $this->json(['message' => 'Ce code existe déjà'], 422);
        $item = (new FuelPaymentMethod())
            ->setStation($station)
            ->setCode($code)
            ->setName($name)
            ->setAllowedRoles($this->allowedRoles($data))
            ->setSupplierDeduction((bool) ($data['supplierDeduction'] ?? false))
            ->setCreatedAt(new \DateTimeImmutable());
        $em->persist($item); $em->flush();
        return $this->json($this->methodRow($item), 201);
    }

    #[Route('/payment-methods/{id}', methods: ['PUT'])]
    public function updateMethod(FuelPaymentMethod $method, Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_GERANT)) return $denied;
        if (!$access->canAccessStation($method->getStation())) return $access->denyStation();
        $data = $request->toArray(); $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') return $this->json(['message' => 'Libellé obligatoire'], 422);
        $method->setName($name)->setAllowedRoles($this->allowedRoles($data))->setSupplierDeduction((bool) ($data['supplierDeduction'] ?? false));
        $em->flush(); return $this->json($this->methodRow($method));
    }

    #[Route('/payment-methods/{id}/deactivate', methods: ['PATCH'])]
    public function deactivateMethod(FuelPaymentMethod $method, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_GERANT)) return $denied;
        if (!$access->canAccessStation($method->getStation())) return $access->denyStation();
        $method->setIsActive(false); $em->flush();
        return $this->json($this->methodRow($method));
    }

    #[Route('/simple-readings', methods: ['POST'])]
    public function createReading(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $data = $request->toArray();
        $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        $nozzle = $em->getRepository(FuelNozzle::class)->find((int) ($data['nozzleId'] ?? 0));
        $attendantId = (int) ($data['attendantId'] ?? 0);
        $attendant = $attendantId ? $em->getRepository(PumpAttendant::class)->find($attendantId) : $nozzle?->getAttendant();
        $customerId = (int) ($data['customerId'] ?? 0);
        $customer = $customerId ? $em->getRepository(Customer::class)->find($customerId) : null;
        if ($station && !$access->canAccessStation($station)) return $access->denyStation();
        if (!$station || !$nozzle || $nozzle->getPump()?->getStation()?->getId() !== $station->getId() || !$attendant || !$attendant->isActive() || $attendant->getStation()?->getId() !== $station->getId() || $nozzle->getAttendant()?->getId() !== $attendant->getId()) return $this->json(['message' => 'Le pistolet doit être attribué au pompiste sélectionné'], 422);
        if ($customerId && (!$customer || !$customer->isActive() || $customer->getStation()?->getId() !== $station->getId())) return $this->json(['message' => 'Client invalide'], 422);
        $start = (float) ($data['startIndex'] ?? 0); $end = (float) ($data['endIndex'] ?? 0);
        $rc = max(0, (float) ($data['returnToTank'] ?? 0)); $output = $end - $start; $sold = $output - $rc;
        $price = (float) $nozzle->getUnitPrice();
        if ($access->canEditFuelUnitPrice() && array_key_exists('unitPrice', $data)) $price = max(0, (float) $data['unitPrice']);
        $total = round($sold * $price, 2);
        if ($output < 0 || $sold < 0) return $this->json(['message' => 'Les index ou le RC sont incohérents'], 422);
        // An index reading can be saved now and settled later. Legacy clients may still
        // submit payment lines here, so keep accepting a complete payment breakdown.
        $lines = $data['payments'] ?? null;
        $payment = []; $paid = 0;
        if ($lines !== null) {
            if (!is_array($lines)) return $this->json(['message' => 'Lignes de paiement invalides'], 422);
            foreach ($lines as $line) {
                if (!is_array($line)) return $this->json(['message' => 'Ligne de paiement invalide'], 422);
                $method = $em->getRepository(FuelPaymentMethod::class)->find((int) ($line['paymentMethodId'] ?? 0));
                $amount = max(0, (float) ($line['amount'] ?? 0));
                if (!$method || !$method->isActive() || !$this->canUseMethod($method, $access) || $method->getStation()?->getId() !== $station->getId()) return $this->json(['message' => 'Mode de paiement non autorisé'], 422);
                if ($customer !== null && $method->getCode() !== 'CLIENT_VOUCHER') return $this->json(['message' => 'En mode crédit, seul le bon client est autorisé.'], 422);
                $paymentEntry = ['type' => $method->getCode(), 'methodId' => $method->getId(), 'label' => $method->getName(), 'supplierDeduction' => $method->isSupplierDeduction(), 'amount' => $amount, 'reference' => trim((string) ($line['reference'] ?? '')) ?: null, 'performedBy' => trim(($access->currentUser()?->getFirstName() ?? '').' '.($access->currentUser()?->getLastName() ?? '')) ?: null];
                if ($amount <= 0) {
                    if ($customer !== null && $method->getCode() === 'CLIENT_VOUCHER') $payment[] = $paymentEntry;
                    continue;
                }
                $paid += $amount;
                $payment[] = $paymentEntry;
            }
        }
        $creditSale = $customer !== null && (bool) ($data['creditMode'] ?? false);
        if ($creditSale && !$payment) $payment[] = ['type' => 'CLIENT_VOUCHER', 'label' => 'Compte client', 'supplierDeduction' => false, 'amount' => 0];
        if ($creditSale) {
            if ($paid > $total + .01) return $this->json(['message' => sprintf('Le paiement ne peut pas dépasser %.2f Ar', $total)], 422);
        } elseif ($paid > 0 && abs($paid - $total) > .01) {
            return $this->json(['message' => sprintf('Le paiement doit être de %.2f Ar', $total)], 422);
        }
        $tank = $nozzle->getTank();
        if ((float) $tank->getCurrentStock() < $sold) return $this->json(['message' => 'Stock cuve insuffisant'], 422);
        $reading = (new FuelShiftReading())->setStation($station)->setNozzle($nozzle)->setAttendant($attendant)->setCustomer($customer)->setWorkDate(new \DateTimeImmutable($data['date'] ?? 'today'))->setStartIndex((string) $start)->setEndIndex((string) $end)->setReturnToTank((string) $rc)->setQuantitySold((string) $sold)->setUnitPrice((string) $price)->setTotalAmount((string) $total)->setPayments($payment)->setStatus($creditSale ? 'CUSTOMER_CREDIT' : ($payment ? 'CLOSED' : 'PENDING'))->setCreatedAt(new \DateTimeImmutable());
        $nozzle->setCurrentIndex((string) $end); $tank->setCurrentStock((string) ((float) $tank->getCurrentStock() - $sold));
        $em->persist($reading); $em->flush();
        $reading->setInvoiceNumber('F-'.(new \DateTimeImmutable())->format('Y').'-'.$reading->getId());
        $em->flush();
        return $this->json(['id' => $reading->getId()], 201);
    }

    #[Route('/readings/{id}/payments', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function addReadingPayments(int $id, Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $reading = $em->getRepository(FuelShiftReading::class)->find($id);
        if (!$reading) return $this->json(['message' => 'Relevé introuvable'], 404);
        if (!$access->canAccessStation($reading->getStation())) return $access->denyStation();
        $isCustomerCredit = count(array_filter($reading->getPayments(), static fn (array $line): bool => ($line['type'] ?? '') === 'CLIENT_VOUCHER')) > 0;
        if ($reading->getCustomer() && $isCustomerCredit) {
            return $this->json(['message' => 'Ce relevé est sur le compte client. Enregistrez le règlement dans le compte client.'], 422);
        }

        $data = $request->toArray();
        $lines = $data['payments'] ?? null;
        if (!is_array($lines) || !$lines) return $this->json(['message' => 'Ajoutez au moins un versement'], 422);
        $existing = $reading->getPayments();
        $alreadyPaid = array_sum(array_map(static fn (array $line): float => (float) ($line['amount'] ?? 0), $existing));
        $remaining = round((float) $reading->getTotalAmount() - $alreadyPaid, 2);
        $newLines = []; $newAmount = 0.0;
        $paidBy = trim(($access->currentUser()?->getFirstName() ?? '').' '.($access->currentUser()?->getLastName() ?? '')) ?: null;
        $date = new \DateTimeImmutable($data['date'] ?? 'today');
        foreach ($lines as $line) {
            if (!is_array($line)) return $this->json(['message' => 'Ligne de versement invalide'], 422);
            $method = $em->getRepository(FuelPaymentMethod::class)->find((int) ($line['paymentMethodId'] ?? 0));
            $amount = round((float) ($line['amount'] ?? 0), 2);
            if (!$method || !$method->isActive() || !$this->canUseMethod($method, $access) || $method->getStation()?->getId() !== $reading->getStation()?->getId()) return $this->json(['message' => 'Mode de paiement non autorisé'], 422);
            if ($amount <= 0) return $this->json(['message' => 'Le montant de chaque versement doit être positif'], 422);
            $newAmount += $amount;
            $newLines[] = ['type' => $method->getCode(), 'methodId' => $method->getId(), 'label' => $method->getName(), 'supplierDeduction' => $method->isSupplierDeduction(), 'amount' => $amount, 'reference' => trim((string) ($line['reference'] ?? '')) ?: null, 'performedBy' => $paidBy, 'date' => $date->format('Y-m-d')];
        }
        if ($newAmount > $remaining + .01) return $this->json(['message' => sprintf('Le versement dépasse le reste dû de %.2f Ar', $remaining)], 422);
        $allPayments = array_merge($existing, $newLines);
        $totalPaid = $alreadyPaid + $newAmount;
        $reading->setPayments($allPayments)->setStatus($totalPaid >= (float) $reading->getTotalAmount() - .01 ? 'CLOSED' : 'PARTIAL');
        $em->flush();
        return $this->json(['id' => $reading->getId(), 'paid' => round($totalPaid, 2), 'remaining' => max(0, round((float) $reading->getTotalAmount() - $totalPaid, 2)), 'status' => $reading->getStatus()]);
    }

    #[Route('/payment-history', methods: ['GET'])]
    public function history(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $stationId = (int) $request->query->get('station');
        if (!$access->canAccessStation($stationId)) return $access->denyStation();
        $readings = $em->getRepository(FuelShiftReading::class)->findBy(['station' => $stationId], ['workDate' => 'DESC', 'id' => 'DESC'], 100);
        $rows = [];
        foreach ($readings as $reading) foreach ($reading->getPayments() as $index => $payment) $rows[] = ['id' => $reading->getId().'-'.$index, 'date' => $payment['date'] ?? $reading->getWorkDate()?->format('Y-m-d'), 'readingDate' => $reading->getWorkDate()?->format('Y-m-d'), 'responsible' => $reading->getAttendant()?->getFullName() ?? 'Non affecté', 'performedBy' => $payment['performedBy'] ?? null, 'nozzle' => $reading->getNozzle()?->getCode(), 'invoiceNumber' => $reading->getInvoiceNumber(), 'method' => $payment['label'] ?? $payment['type'] ?? '—', 'reference' => $payment['reference'] ?? null, 'amount' => (float) ($payment['amount'] ?? 0)];
        return $this->json(['payments' => $rows]);
    }

    private function methodRow(FuelPaymentMethod $method): array
    {
        return ['id' => $method->getId(), 'code' => $method->getCode(), 'name' => $method->getName(), 'active' => $method->isActive(), 'supplierDeduction' => $method->isSupplierDeduction(), 'allowedRoles' => $method->getAllowedRoles()];
    }

    private function allowedRoles(array $data): array
    {
        $allowed = [UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL, UserAccessService::ROLE_ASSISTANT];
        $roles = array_values(array_intersect($allowed, array_filter((array) ($data['allowedRoles'] ?? []), 'is_string')));
        return $roles ?: [UserAccessService::ROLE_GERANT];
    }

    private function canUseMethod(FuelPaymentMethod $method, UserAccessService $access): bool
    {
        return $method->canBeUsedBy($access->currentUser());
    }
}
