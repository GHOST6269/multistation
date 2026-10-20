<?php

namespace App\Controller;

use App\Entity\{CustomerPayment, Expense, FuelShiftReading, Stations, SupplierInvoice, SupplierPayment};
use App\Repository\StationsRepository;
use App\Repository\UserRepository;
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ApiController extends AbstractController
{
    #[Route('/dashboard', methods: ['GET'])]
    public function dashboard(Request $request, StationsRepository $stations, UserRepository $users, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $allowedIds = $access->allowedStationIds();
        $allStations = $access->isSuperAdmin() ? $stations->findBy([], ['id' => 'DESC']) : array_values(array_filter($stations->findAll(), static fn (Stations $station): bool => in_array($station->getId(), $allowedIds, true)));
        $stationId = (int) $request->query->get('station', 0);
        $selectedStation = $stationId ? $stations->find($stationId) : ($allStations[0] ?? null);
        if ($stationId && !$selectedStation) return $this->json(['message' => 'Station introuvable.'], 404);
        if ($selectedStation && !$access->canAccessStation($selectedStation)) return $access->denyStation();

        $today = new \DateTimeImmutable('today');
        $defaultFrom = $today->modify('first day of this month')->format('Y-m-d');
        $from = $this->dashboardDate((string) $request->query->get('from', $defaultFrom));
        $to = $this->dashboardDate((string) $request->query->get('to', $today->format('Y-m-d')));
        if (!$from || !$to || $from > $to) return $this->json(['message' => 'La période sélectionnée est invalide.'], 422);
        $canViewSuppliers = $access->isSuperAdmin() || in_array(UserAccessService::ROLE_GERANT, $access->currentUser()?->getRoles() ?? [], true);
        $finance = $selectedStation ? $this->stationFinance($selectedStation, $from, $to, $em, $access->canViewCustomers(), $canViewSuppliers) : null;

        return $this->json([
            'metrics' => [
                'stations' => count($allStations),
                'activeStations' => count(array_filter($allStations, static fn (Stations $station): bool => strtoupper($station->getStatus() ?? '') === 'ACTIVE')),
                'users' => $users->count(['isActive' => true]),
            ],
            'stationsForSelection' => array_map(static fn (Stations $station): array => ['id' => $station->getId(), 'name' => $station->getName(), 'status' => $station->getStatus()], $allStations),
            'selectedStationId' => $selectedStation?->getId(),
            'finance' => $finance,
            'stations' => array_map(static fn (Stations $station): array => [
                'id' => $station->getId(),
                'name' => $station->getName(),
                'city' => $station->getCity(),
                'status' => $station->getStatus(),
                'manager' => $station->getGerant(),
            ], array_slice($allStations, 0, 5)),
        ]);
    }

    private function stationFinance(Stations $station, string $from, string $to, EntityManagerInterface $em, bool $canViewCustomers, bool $canViewSuppliers): array
    {
        $readings = $em->getRepository(FuelShiftReading::class)->findBy(['station' => $station], ['workDate' => 'DESC', 'id' => 'DESC']);
        $revenue = 0.0;
        foreach ($readings as $reading) {
            $date = $reading->getWorkDate()?->format('Y-m-d');
            if ($date !== null && $date >= $from && $date <= $to) $revenue += (float) $reading->getTotalAmount();
        }

        $expenses = $em->getRepository(Expense::class)->findBy(['station' => $station], ['expenseDate' => 'DESC', 'id' => 'DESC']);
        $expenseTotal = 0.0;
        $expenseRows = [];
        foreach ($expenses as $expense) {
            $date = $expense->getExpenseDate()?->format('Y-m-d');
            if ($date === null || $date < $from || $date > $to) continue;
            $amount = (float) $expense->getAmount();
            $expenseTotal += $amount;
            if (count($expenseRows) < 6) $expenseRows[] = ['id' => $expense->getId(), 'date' => $date, 'category' => $expense->getCategory(), 'description' => $expense->getDescription(), 'amount' => $amount];
        }

        $receivable = null;
        if ($canViewCustomers) {
            $receivable = 0.0;
            $billedByCustomer = [];
            $paidByCustomer = [];
            foreach ($readings as $reading) {
                $customerId = $reading->getCustomer()?->getId();
                $lines = array_values(array_filter($reading->getPayments(), 'is_array'));
                $creditLines = array_values(array_filter($lines, static fn (array $line): bool => !empty($line['isCredit']) || strtoupper((string) ($line['type'] ?? '')) === 'CLIENT_VOUCHER'));
                if ($creditLines) {
                    foreach ($creditLines as $line) {
                        $lineCustomerId = (int) ($line['customerId'] ?? $customerId ?? 0);
                        if (!$lineCustomerId) continue;
                        $amount = (float) ($line['amount'] ?? 0);
                        if ($amount <= 0 && count($creditLines) === 1) $amount = (float) $reading->getTotalAmount();
                        $billedByCustomer[$lineCustomerId] = ($billedByCustomer[$lineCustomerId] ?? 0.0) + $amount;
                    }
                    continue;
                }
                if (!$customerId) continue;
                $billedByCustomer[$customerId] = ($billedByCustomer[$customerId] ?? 0.0) + (float) $reading->getTotalAmount();
                $paidByCustomer[$customerId] = ($paidByCustomer[$customerId] ?? 0.0) + array_sum(array_map(static fn (array $line): float => (float) ($line['amount'] ?? 0), $lines));
            }
            foreach ($em->getRepository(CustomerPayment::class)->findBy(['station' => $station]) as $payment) {
                $customerId = $payment->getCustomer()?->getId();
                if ($customerId) $paidByCustomer[$customerId] = ($paidByCustomer[$customerId] ?? 0.0) + (float) $payment->getAmount();
            }
            foreach (array_unique(array_merge(array_keys($billedByCustomer), array_keys($paidByCustomer))) as $customerId) {
                $receivable += max(0.0, ($billedByCustomer[$customerId] ?? 0.0) - ($paidByCustomer[$customerId] ?? 0.0));
            }
        }

        $payable = null;
        if ($canViewSuppliers) {
            $paidByInvoice = [];
            foreach ($em->getRepository(SupplierPayment::class)->findBy(['station' => $station, 'status' => 'EXECUTED']) as $payment) {
                $invoiceId = $payment->getInvoice()?->getId();
                if ($invoiceId) $paidByInvoice[$invoiceId] = ($paidByInvoice[$invoiceId] ?? 0.0) + (float) $payment->getAmount();
            }
            $payable = 0.0;
            foreach ($em->getRepository(SupplierInvoice::class)->findBy(['station' => $station, 'isActive' => true]) as $invoice) {
                $payable += max(0.0, (float) $invoice->getTotalAmount() - ($paidByInvoice[$invoice->getId()] ?? 0.0));
            }
        }

        return [
            'stationId' => $station->getId(), 'stationName' => $station->getName(),
            'from' => $from, 'to' => $to,
            'customerReceivable' => $receivable === null ? null : round($receivable, 2),
            'supplierPayable' => $payable === null ? null : round($payable, 2),
            'revenue' => round($revenue, 2), 'expenses' => round($expenseTotal, 2),
            'expenseRows' => $expenseRows,
        ];
    }

    private function dashboardDate(string $value): ?string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) return null;
        return $value;
    }

    #[Route('/stations', methods: ['GET'])]
    public function stations(Request $request, StationsRepository $repository, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $search = trim((string) $request->query->get('search', ''));
        $stations = $search === '' ? $repository->findBy([], ['id' => 'DESC']) : $repository->createQueryBuilder('s')
            ->andWhere('LOWER(s.name) LIKE :search OR LOWER(s.city) LIKE :search OR LOWER(s.code) LIKE :search')
            ->setParameter('search', '%'.mb_strtolower($search).'%')->orderBy('s.id', 'DESC')->getQuery()->getResult();
        if (!$access->isSuperAdmin()) {
            $allowedIds = $access->allowedStationIds();
            $stations = array_values(array_filter($stations, static fn (Stations $station): bool => in_array($station->getId(), $allowedIds, true)));
        }
        return $this->json(array_map($this->stationRow(...), $stations));
    }

    #[Route('/stations', methods: ['POST'])]
    public function createStation(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_SUPER_ADMIN)) return $denied;
        $data = $request->toArray();
        if (trim((string) ($data['name'] ?? '')) === '') return $this->json(['message' => 'Le nom de la station est obligatoire.'], 422);
        $station = $this->applyStationData((new Stations())->setCreatAt(new \DateTime()), $data);
        $em->persist($station); $em->flush();
        return $this->json($this->stationRow($station), 201);
    }

    #[Route('/stations/{id}', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function updateStation(Stations $station, Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_SUPER_ADMIN)) return $denied;
        $data = $request->toArray();
        if (trim((string) ($data['name'] ?? '')) === '') return $this->json(['message' => 'Le nom de la station est obligatoire.'], 422);
        $this->applyStationData($station, $data)->setUpdatedAt(new \DateTime());
        $em->flush();
        return $this->json($this->stationRow($station));
    }

    #[Route('/stations/{id}/deactivate', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function deactivateStation(Stations $station, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_SUPER_ADMIN)) return $denied;
        $station->setStatus('INACTIVE')->setUpdatedAt(new \DateTime());
        $em->flush();
        return $this->json($this->stationRow($station));
    }

    #[Route('/stations/{id}/activate', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function activateStation(Stations $station, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require(UserAccessService::ROLE_SUPER_ADMIN)) return $denied;
        $station->setStatus('ACTIVE')->setUpdatedAt(new \DateTime());
        $em->flush();
        return $this->json($this->stationRow($station));
    }

    private function stationRow(Stations $station): array
    {
        return [
            'id' => $station->getId(),
            'code' => $station->getCode(),
            'name' => $station->getName(),
            'address' => $station->getAddress(),
            'city' => $station->getCity(),
            'contact' => $station->getContact(),
            'email' => $station->getEmail(),
            'status' => $station->getStatus(),
            'manager' => $station->getGerant(),
            'usersCount' => $station->getStationUsers()->count(),
            'createdAt' => $station->getCreatAt()?->format(DATE_ATOM),
        ];
    }

    private function applyStationData(Stations $station, array $data): Stations
    {
        $nullable = static fn (mixed $value): ?string => trim((string) $value) === '' ? null : trim((string) $value);
        return $station->setName(trim((string) $data['name']))
            ->setCode($nullable($data['code'] ?? null))->setCity($nullable($data['city'] ?? null))
            ->setAddress($nullable($data['address'] ?? null))->setContact($nullable($data['contact'] ?? null))
            ->setEmail($nullable($data['email'] ?? null))->setGerant($nullable($data['manager'] ?? null))
            ->setStatus((string) ($data['status'] ?? $station->getStatus() ?? 'ACTIVE'));
    }
}
