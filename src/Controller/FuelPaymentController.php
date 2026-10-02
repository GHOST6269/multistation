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
        if ($mode === 'all') return $methods;
        return array_values(array_filter($methods, static fn (array $method): bool => (bool) ($method['isCredit'] ?? false) === ($mode === 'credit')));
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
                $item = (new FuelPaymentMethod())->setStation($station)->setCode($code)->setName($name)->setAllowedRoles($roles)->setSupplierDeduction(in_array($code, ['FANILO', 'TPE', 'VISA', 'FMS'], true))->setIsCredit($code === 'CLIENT_VOUCHER')->setCreatedAt(new \DateTimeImmutable());
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
        if ($mode !== 'all') $methods = self::filterMethodsForMode($methods, $mode);
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
            ->setIsCredit((bool) ($data['isCredit'] ?? false))
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
        $method->setName($name)->setAllowedRoles($this->allowedRoles($data))->setSupplierDeduction((bool) ($data['supplierDeduction'] ?? false))->setIsCredit((bool) ($data['isCredit'] ?? false));
        $em->flush(); return $this->json($this->methodRow($method));
    }

    #[Route('/payment-methods/{id}/activate', methods: ['PATCH'])]
    public function activateMethod(FuelPaymentMethod $method, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_GERANT)) return $denied;
        if (!$access->canAccessStation($method->getStation())) return $access->denyStation();
        $method->setIsActive(true); $em->flush();
        return $this->json($this->methodRow($method));
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
        // Batch submissions keep the existing single-reading contract available to
        // older clients while letting the attendant form submit several nozzles.
        if (isset($data['readings'])) {
            if (!is_array($data['readings']) || !$data['readings']) return $this->json(['message' => 'Ajoutez au moins un pistolet'], 422);
            $results = [];
            $em->getConnection()->beginTransaction();
            foreach ($data['readings'] as $line) {
                if (!is_array($line)) { $em->getConnection()->rollBack(); return $this->json(['message' => 'Relevé de pistolet invalide'], 422); }
                if ((float) ($line['endIndex'] ?? 0) === (float) ($line['startIndex'] ?? 0)) continue;
                $line['stationId'] = $data['stationId'] ?? 0;
                $line['attendantId'] = $data['attendantId'] ?? 0;
                $line['date'] = $data['date'] ?? 'today';
                if (!empty($data['creditMode'])) {
                    $line['customerId'] = $data['customerId'] ?? 0;
                    $line['creditMode'] = true;
                    $line['dueDate'] = $data['dueDate'] ?? '';
                }
                $subRequest = Request::create('/api/fuel/simple-readings', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($line));
                $response = $this->createReading($subRequest, $em, $access);
                if ($response->getStatusCode() >= 400) { $em->getConnection()->rollBack(); return $response; }
                $results[] = json_decode((string) $response->getContent(), true);
            }
            if (!$results) { $em->getConnection()->rollBack(); return $this->json(['message' => 'Aucun pistolet à enregistrer : les index départ et final sont identiques.'], 422); }
            $em->getConnection()->commit();
            return $this->json(['readings' => $results], 201);
        }
        $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        $nozzle = $em->getRepository(FuelNozzle::class)->find((int) ($data['nozzleId'] ?? 0));
        $attendantId = (int) ($data['attendantId'] ?? 0);
        $attendant = $attendantId ? $em->getRepository(PumpAttendant::class)->find($attendantId) : $nozzle?->getAttendant();
        $customerId = (int) ($data['customerId'] ?? 0);
        $customer = $customerId ? $em->getRepository(Customer::class)->find($customerId) : null;
        if (!empty($data['creditMode']) && !$customerId) return $this->json(['message' => 'Le client est obligatoire pour un relevé client.'], 422);
        if (!empty($data['creditMode']) && empty($data['dueDate'])) return $this->json(['message' => 'La date d’échéance est obligatoire pour un relevé client.'], 422);
        if ($station && !$access->canAccessStation($station)) return $access->denyStation();
        if (!$station || !$nozzle || $nozzle->getPump()?->getStation()?->getId() !== $station->getId() || !$attendant || !$attendant->isActive() || $attendant->getStation()?->getId() !== $station->getId() || $nozzle->getAttendant()?->getId() !== $attendant->getId()) return $this->json(['message' => 'Le pistolet doit être attribué au pompiste sélectionné'], 422);
        if ($customerId && (!$customer || !$customer->isActive() || $customer->getStation()?->getId() !== $station->getId())) return $this->json(['message' => 'Client invalide'], 422);
        $start = (float) ($data['startIndex'] ?? 0); $end = (float) ($data['endIndex'] ?? 0);
        if ($end === $start) return $this->json(['message' => 'Les index départ et final sont identiques : aucun relevé à enregistrer.'], 422);
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
                if ($customer !== null && !$method->isCredit()) return $this->json(['message' => 'En mode crédit, seul un paiement à crédit est autorisé.'], 422);
                $paymentEntry = ['type' => $method->getCode(), 'methodId' => $method->getId(), 'label' => $method->getName(), 'supplierDeduction' => $method->isSupplierDeduction(), 'isCredit' => $method->isCredit(), 'amount' => $amount, 'reference' => trim((string) ($line['reference'] ?? '')) ?: null, 'performedBy' => trim(($access->currentUser()?->getFirstName() ?? '').' '.($access->currentUser()?->getLastName() ?? '')) ?: null];
                if ($amount <= 0) {
                    if ($customer !== null && $method->isCredit()) $payment[] = $paymentEntry;
                    continue;
                }
                $paid += $amount;
                $payment[] = $paymentEntry;
            }
        }
        $creditSale = $customer !== null && (bool) ($data['creditMode'] ?? false);
        if ($creditSale && !$payment) $payment[] = ['type' => 'CLIENT_VOUCHER', 'label' => 'Compte client', 'supplierDeduction' => false, 'isCredit' => true, 'amount' => 0];
        if ($creditSale) {
            if ($paid > $total + .01) return $this->json(['message' => sprintf('Le paiement ne peut pas dépasser %.2f Ar', $total)], 422);
        } elseif ($paid > 0 && abs($paid - $total) > .01) {
            return $this->json(['message' => sprintf('Le paiement doit être de %.2f Ar', $total)], 422);
        }
        $tank = $nozzle->getTank();
        if ((float) $tank->getCurrentStock() < $sold) return $this->json(['message' => 'Stock cuve insuffisant'], 422);
        $reading = (new FuelShiftReading())->setStation($station)->setNozzle($nozzle)->setAttendant($attendant)->setCustomer($customer)->setDueDate(!empty($data['dueDate']) ? new \DateTimeImmutable($data['dueDate']) : null)->setWorkDate(new \DateTimeImmutable($data['date'] ?? 'today'))->setStartIndex((string) $start)->setEndIndex((string) $end)->setReturnToTank((string) $rc)->setQuantitySold((string) $sold)->setUnitPrice((string) $price)->setTotalAmount((string) $total)->setPayments($payment)->setStatus($creditSale ? 'CUSTOMER_CREDIT' : ($payment ? 'CLOSED' : 'PENDING'))->setCreatedAt(new \DateTimeImmutable());
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
        $isCustomerCredit = count(array_filter($reading->getPayments(), static fn (array $line): bool => !empty($line['isCredit']) || ($line['type'] ?? '') === 'CLIENT_VOUCHER')) > 0;
        if ($reading->getCustomer() && $isCustomerCredit) {
            return $this->json(['message' => 'Ce relevé est sur le compte client. Enregistrez le règlement dans le compte client.'], 422);
        }

        $data = $request->toArray();
        $lines = $data['payments'] ?? null;
        if (!is_array($lines) || !$lines) return $this->json(['message' => 'Ajoutez au moins un versement'], 422);
        $customer = $reading->getCustomer();
        if (!empty($data['customerId'])) {
            $selectedCustomer = $em->getRepository(Customer::class)->find((int) $data['customerId']);
            if (!$selectedCustomer || !$selectedCustomer->isActive() || $selectedCustomer->getStation()?->getId() !== $reading->getStation()?->getId()) return $this->json(['message' => 'Client invalide'], 422);
            if ($customer && $customer->getId() !== $selectedCustomer->getId()) return $this->json(['message' => 'Ce relevé est déjà associé à un autre client.'], 422);
            $customer = $selectedCustomer;
        }
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
            if ($method->isCredit() && !$customer) return $this->json(['message' => 'Le client est obligatoire pour un paiement à crédit.'], 422);
            if ($amount <= 0) return $this->json(['message' => 'Le montant de chaque versement doit être positif'], 422);
            $newAmount += $amount;
            $newLines[] = ['type' => $method->getCode(), 'methodId' => $method->getId(), 'label' => $method->getName(), 'supplierDeduction' => $method->isSupplierDeduction(), 'isCredit' => $method->isCredit(), 'customerId' => $method->isCredit() ? $customer?->getId() : null, 'customerName' => $method->isCredit() ? $customer?->getName() : null, 'amount' => $amount, 'reference' => trim((string) ($line['reference'] ?? '')) ?: null, 'performedBy' => $paidBy, 'date' => $date->format('Y-m-d')];
        }
        if ($newAmount > $remaining + .01) return $this->json(['message' => sprintf('Le versement dépasse le reste dû de %.2f Ar', $remaining)], 422);
        $allPayments = array_merge($existing, $newLines);
        $totalPaid = $alreadyPaid + $newAmount;
        if (array_filter($newLines, static fn (array $line): bool => !empty($line['isCredit']))) $reading->setCustomer($customer);
        $reading->setPayments($allPayments)->setStatus($totalPaid >= (float) $reading->getTotalAmount() - .01 ? 'CLOSED' : 'PARTIAL');
        $em->flush();
        return $this->json(['id' => $reading->getId(), 'paid' => round($totalPaid, 2), 'remaining' => max(0, round((float) $reading->getTotalAmount() - $totalPaid, 2)), 'status' => $reading->getStatus()]);
    }

    #[Route('/attendant-settlements', methods: ['POST'])]
    public function settleAttendant(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $data = $request->toArray();
        $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        $attendant = $em->getRepository(PumpAttendant::class)->find((int) ($data['attendantId'] ?? 0));
        if (!$station || !$attendant || $attendant->getStation()?->getId() !== $station->getId() || !$access->canAccessStation($station)) return $this->json(['message' => 'Station ou pompiste invalide'], 422);
        $customer = !empty($data['customerId']) ? $em->getRepository(Customer::class)->find((int) $data['customerId']) : null;
        if (!empty($data['customerId']) && (!$customer || !$customer->isActive() || $customer->getStation()?->getId() !== $station->getId())) return $this->json(['message' => 'Client invalide'], 422);
        $date = new \DateTimeImmutable($data['date'] ?? 'today');
        $readings = $em->getRepository(FuelShiftReading::class)->findBy(['station' => $station, 'attendant' => $attendant], ['workDate' => 'ASC', 'id' => 'ASC']);
        $due = [];
        foreach ($readings as $reading) {
            $credit = count(array_filter($reading->getPayments(), static fn (array $line): bool => !empty($line['isCredit']) || ($line['type'] ?? '') === 'CLIENT_VOUCHER')) > 0;
            $remaining = round((float) $reading->getTotalAmount() - array_sum(array_map(static fn (array $line): float => (float) ($line['amount'] ?? 0), $reading->getPayments())), 2);
            // Customer-account sales belong to the customer ledger; never use the
            // attendant's payment to settle them, even if a customer was selected.
            if (!$credit && $remaining > .01) $due[] = ['reading' => $reading, 'remaining' => $remaining];
        }
        $lines = $data['payments'] ?? [];
        if (!is_array($lines) || !$lines) return $this->json(['message' => 'Ajoutez au moins un mode de paiement'], 422);
        $total = 0.0; $validLines = [];
        foreach ($lines as $line) {
            $method = is_array($line) ? $em->getRepository(FuelPaymentMethod::class)->find((int) ($line['paymentMethodId'] ?? 0)) : null;
            $amount = round((float) ($line['amount'] ?? 0), 2);
            if (!$method || !$method->isActive() || !$this->canUseMethod($method, $access) || $method->getStation()?->getId() !== $station->getId() || $amount <= 0) return $this->json(['message' => 'Mode ou montant de versement invalide'], 422);
            $lineCustomer = null;
            if ($method->isCredit()) {
                $lineCustomerId = (int) ($line['customerId'] ?? $data['customerId'] ?? 0);
                $lineCustomer = $lineCustomerId ? $em->getRepository(Customer::class)->find($lineCustomerId) : null;
                if (!$lineCustomer || !$lineCustomer->isActive() || $lineCustomer->getStation()?->getId() !== $station->getId()) return $this->json(['message' => 'Le client est obligatoire pour chaque paiement par bons clients.'], 422);
            }
            $validLines[] = [$method, $amount, trim((string) ($line['reference'] ?? '')) ?: null, $lineCustomer]; $total += $amount;
        }
        $available = array_sum(array_column($due, 'remaining'));
        $cashTotal = array_sum(array_map(static fn (array $line): float => $line[0]->isCredit() ? 0.0 : $line[1], $validLines));
        if ($cashTotal > $available + .01) return $this->json(['message' => sprintf('Les paiements encaissés dépassent le total restant dû de %.2f Ar', $available)], 422);
        $paidBy = trim(($access->currentUser()?->getFirstName() ?? '').' '.($access->currentUser()?->getLastName() ?? '')) ?: null;
        foreach ($validLines as [$method, $amount, $reference, $lineCustomer]) {
            $left = $amount;
            foreach ($due as &$item) {
                if ($left <= .001) break;
                $part = min($left, $item['remaining']);
                if ($part <= 0) continue;
                $reading = $item['reading']; $payments = $reading->getPayments();
                $isVoucher = $method->isCredit();
                if ($isVoucher && $reading->getCustomer() && $reading->getCustomer()?->getId() !== $lineCustomer?->getId()) continue;
                $payments[] = ['type' => $method->getCode(), 'methodId' => $method->getId(), 'label' => $method->getName(), 'supplierDeduction' => $method->isSupplierDeduction(), 'isCredit' => $method->isCredit(), 'amount' => round($part, 2), 'reference' => $reference, 'performedBy' => $paidBy, 'date' => $date->format('Y-m-d')];
                $item['remaining'] = round($item['remaining'] - $part, 2); $left = round($left - $part, 2);
                $paid = array_sum(array_map(static fn (array $line): float => (float) ($line['amount'] ?? 0), $payments));
                if ($isVoucher) {
                    $payments[array_key_last($payments)]['customerId'] = $lineCustomer?->getId();
                    $payments[array_key_last($payments)]['customerName'] = $lineCustomer?->getName();
                    if (!$reading->getCustomer()) $reading->setCustomer($lineCustomer);
                }
                $reading->setPayments($payments)->setStatus($paid >= (float) $reading->getTotalAmount() - .01 ? 'CLOSED' : 'PARTIAL');
            }
            unset($item);
            if ($left > .01 && $method->isCredit() && $readings) {
                // Any amount beyond the attendant's outstanding sales is still a
                // customer credit purchase, so retain it as customer debt.
                $target = $due[0]['reading'] ?? $readings[0];
                foreach ($due as $candidate) {
                    if (!$candidate['reading']->getCustomer() || $candidate['reading']->getCustomer()?->getId() === $lineCustomer?->getId()) {
                        $target = $candidate['reading'];
                        break;
                    }
                }
                $payments = $target->getPayments();
                $payments[] = ['type' => $method->getCode(), 'methodId' => $method->getId(), 'label' => $method->getName(), 'supplierDeduction' => $method->isSupplierDeduction(), 'isCredit' => true, 'amount' => round($left, 2), 'customerId' => $lineCustomer?->getId(), 'customerName' => $lineCustomer?->getName(), 'reference' => $reference, 'performedBy' => $paidBy, 'date' => $date->format('Y-m-d')];
                if (!$target->getCustomer()) $target->setCustomer($lineCustomer);
                $paid = array_sum(array_map(static fn (array $entry): float => (float) ($entry['amount'] ?? 0), $payments));
                $target->setPayments($payments)->setStatus($paid >= (float) $target->getTotalAmount() - .01 ? 'CLOSED' : 'PARTIAL');
            }
        }
        $em->flush();
        return $this->json(['paid' => round($total, 2), 'remaining' => max(0, round($available - $total, 2))]);
    }

    #[Route('/payment-history', methods: ['GET'])]
    public function history(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $stationId = (int) $request->query->get('station');
        if (!$access->canAccessStation($stationId)) return $access->denyStation();
        $readings = $em->getRepository(FuelShiftReading::class)->findBy(['station' => $stationId], ['workDate' => 'DESC', 'id' => 'DESC'], 100);
        $rows = []; $grouped = [];
        foreach ($readings as $reading) foreach ($reading->getPayments() as $index => $payment) {
            if (!empty($payment['customerAccountPayment']) || str_starts_with((string) ($payment['label'] ?? ''), 'Règlement client ·')) continue;
            $date = $payment['date'] ?? $reading->getWorkDate()?->format('Y-m-d');
            $method = $payment['label'] ?? $payment['type'] ?? '—';
            $isCredit = !empty($payment['isCredit']) || strtoupper((string) ($payment['type'] ?? '')) === 'CLIENT_VOUCHER';
            $row = ['id' => $reading->getId().'-'.$index, 'date' => $date, 'readingDate' => $reading->getWorkDate()?->format('Y-m-d'), 'responsible' => $reading->getAttendant()?->getFullName() ?? 'Non affecté', 'attendantId' => $reading->getAttendant()?->getId(), 'performedBy' => $payment['performedBy'] ?? null, 'nozzle' => $reading->getNozzle()?->getCode(), 'invoiceNumber' => $reading->getInvoiceNumber(), 'method' => $method, 'methodId' => $payment['methodId'] ?? null, 'type' => $payment['type'] ?? null, 'isCredit' => $isCredit, 'reference' => $payment['reference'] ?? null, 'amount' => (float) ($payment['amount'] ?? 0)];
            if ($isCredit) { $rows[] = $row; continue; }
            $methodKey = $row['methodId'] ?? $row['type'] ?? $method;
            $key = implode('|', [(string) $date, (string) $row['attendantId'], (string) $methodKey]);
            if (!isset($grouped[$key])) {
                $grouped[$key] = $row;
                $grouped[$key]['id'] = 'group-'.$key;
                $grouped[$key]['nozzles'] = [];
                $grouped[$key]['references'] = [];
                $rows[] = &$grouped[$key];
            }
            $grouped[$key]['amount'] += $row['amount'];
            if ($row['nozzle']) $grouped[$key]['nozzles'][$row['nozzle']] = $row['nozzle'];
            if ($row['reference']) $grouped[$key]['references'][$row['reference']] = $row['reference'];
        }
        foreach ($rows as &$row) {
            if (isset($row['nozzles'])) $row['nozzle'] = implode(', ', array_values($row['nozzles']));
            if (isset($row['references'])) $row['reference'] = implode(' / ', array_values($row['references']));
            unset($row['nozzles'], $row['references']);
        }
        unset($row);
        return $this->json(['payments' => $rows]);
    }

    private function methodRow(FuelPaymentMethod $method): array
    {
        return ['id' => $method->getId(), 'code' => $method->getCode(), 'name' => $method->getName(), 'active' => $method->isActive(), 'supplierDeduction' => $method->isSupplierDeduction(), 'isCredit' => $method->isCredit(), 'allowedRoles' => $method->getAllowedRoles()];
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
