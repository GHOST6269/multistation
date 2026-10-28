<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Entity\FuelPaymentMethod;
use App\Entity\Stations;
use App\Entity\Supplier;
use App\Entity\SupplierInvoice;
use App\Entity\SupplierPayment;
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/expenses')]
final class ExpenseController extends AbstractController
{
    private const DEFAULT_METHODS = [
        'CASH' => 'Espèces', 'CHEQUE' => 'Chèque', 'TPE' => 'Carte TPE',
        'FANILO' => 'Carte FANILO', 'VISA' => 'Carte Visa', 'FMS' => 'FMS',
        'CLIENT_VOUCHER' => 'Bons clients', 'STATION_OPERATION' => 'Fonctionnement station',
    ];

    #[Route('', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $this->authorize($access)) return $denied;
        $station = $em->getRepository(Stations::class)->find((int) $request->query->get('station'));
        if (!$station || !$access->canAccessStation($station)) return $access->denyStation();
        $expenses = $em->getRepository(Expense::class)->findBy(['station' => $station], ['expenseDate' => 'DESC', 'id' => 'DESC']);
        return $this->json(['expenses' => array_map(fn (Expense $expense): array => $this->row($expense), $expenses)]);
    }

    #[Route('/options', methods: ['GET'])]
    public function options(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $this->authorize($access)) return $denied;
        $station = $em->getRepository(Stations::class)->find((int) $request->query->get('station'));
        if (!$station || !$access->canAccessStation($station)) return $access->denyStation();
        $configuredMethods = $em->getRepository(FuelPaymentMethod::class)->findBy(['station' => $station]);
        if (!$configuredMethods) {
            foreach (self::DEFAULT_METHODS as $code => $name) {
                $journalType = $code === 'CLIENT_VOUCHER' ? 'CREDIT' : (in_array($code, ['FANILO', 'TPE', 'VISA', 'FMS'], true) ? 'SUPPLIER' : ($code === 'CASH' ? 'CASH' : 'BANK'));
                $method = (new FuelPaymentMethod())->setStation($station)->setCode($code)->setName($name)->setJournalType($journalType)->setSupplierDeduction($journalType === 'SUPPLIER')->setIsCredit($journalType === 'CREDIT')->setAllowedRoles($code === 'CASH' ? [UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT] : [UserAccessService::ROLE_GERANT])->setCreatedAt(new \DateTimeImmutable());
                $em->persist($method);
                $configuredMethods[] = $method;
            }
            $em->flush();
        }
        $methods = array_values(array_filter(
            array_values(array_filter($configuredMethods, static fn (FuelPaymentMethod $method): bool => $method->isActive())),
            fn (FuelPaymentMethod $method): bool => $method->isAvailableForExpenses() && $method->canBeUsedBy($access->currentUser()),
        ));
        $suppliers = $em->getRepository(Supplier::class)->findBy(['station' => $station, 'isActive' => true], ['name' => 'ASC']);
        return $this->json([
            'methods' => array_map(fn (FuelPaymentMethod $method): array => ['code' => $method->getCode(), 'name' => $method->getName(), 'journalType' => $this->methodType($method)], $methods),
            'suppliers' => array_map(static fn (Supplier $supplier): array => ['id' => $supplier->getId(), 'name' => $supplier->getName()], $suppliers),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $this->authorize($access)) return $denied;
        $data = $request->toArray();
        $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $description = trim((string) ($data['description'] ?? ''));
        if (!$station || !$access->canAccessStation($station)) return $access->denyStation();
        if (!$access->currentUser() || $amount <= 0 || $description === '') return $this->json(['message' => 'Description et montant obligatoires'], 422);

        $supplier = null;
        if (!empty($data['supplierId'])) {
            $supplier = $em->getRepository(Supplier::class)->find((int) $data['supplierId']);
            if (!$supplier || !$supplier->isActive() || $supplier->getStation()?->getId() !== $station->getId()) return $this->json(['message' => 'Fournisseur invalide'], 422);
            $invoiceNumber = trim((string) ($data['reference'] ?? ''));
            if ($invoiceNumber === '' || mb_strlen($invoiceNumber) > 60) return $this->json(['message' => 'Le numéro de facture fournisseur est obligatoire (60 caractères maximum).'], 422);
            if ($em->getRepository(SupplierInvoice::class)->findOneBy(['station' => $station, 'supplier' => $supplier, 'invoiceNumber' => $invoiceNumber, 'isActive' => true])) {
                return $this->json(['message' => 'Cette facture existe déjà pour ce fournisseur.'], 422);
            }
        }

        $paymentMethod = trim((string) ($data['paymentMethod'] ?? '')) ?: 'CASH';
        $expenseDate = new \DateTimeImmutable($data['date'] ?? 'today');
        $expense = (new Expense())
            ->setStation($station)
            ->setPerformedBy($access->currentUser())
            ->setSupplier($supplier)
            ->setCategory(trim((string) ($data['category'] ?? '')) ?: 'Autre')
            ->setDescription($description)
            ->setAmount((string) $amount)
            ->setExpenseDate($expenseDate)
            ->setPaymentMethod($paymentMethod)
            ->setReference(trim((string) ($data['reference'] ?? '')) ?: null)
            ->setCreatedAt(new \DateTimeImmutable());
        $em->persist($expense);

        if ($supplier) {
            $invoice = (new SupplierInvoice())
                ->setSupplier($supplier)
                ->setStation($station)
                ->setInvoiceNumber($invoiceNumber)
                ->setInvoiceDate($expenseDate)
                ->setTotalAmount((string) $amount)
                ->setInvoiceType('EXPENSE')
                ->setDescription('Dépense : '.$expense->getCategory().' · '.$description)
                ->setCreatedAt(new \DateTimeImmutable());
            $payment = (new SupplierPayment())
                ->setSupplier($supplier)
                ->setInvoice($invoice)
                ->setStation($station)
                ->setPerformedBy($access->currentUser())
                ->setPaymentDate($expenseDate)
                ->setAmount((string) $amount)
                ->setPaymentMethod($paymentMethod)
                ->setReference($expense->getReference())
                ->setStatus('EXECUTED')
                ->setNote('Paiement intégral lié à la dépense : '.$description)
                ->setCreatedAt(new \DateTimeImmutable());
            $em->persist($invoice);
            $em->persist($payment);
        }

        $em->flush();
        return $this->json($this->row($expense), 201);
    }

    private function authorize(UserAccessService $access): ?JsonResponse
    {
        return $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT]);
    }

    private function methodType(FuelPaymentMethod $method): string
    {
        if ($method->isCredit()) return 'CREDIT';
        if ($method->isSupplierDeduction()) return 'SUPPLIER';
        return strtoupper($method->getJournalType());
    }

    private function row(Expense $expense): array
    {
        return [
            'id' => $expense->getId(),
            'date' => $expense->getExpenseDate()?->format('Y-m-d'),
            'category' => $expense->getCategory(),
            'description' => $expense->getDescription(),
            'amount' => (float) $expense->getAmount(),
            'method' => $expense->getPaymentMethod(),
            'reference' => $expense->getReference(),
            'supplierId' => $expense->getSupplier()?->getId(),
            'supplier' => $expense->getSupplier()?->getName(),
            'performedBy' => $expense->getPerformedBy() ? trim($expense->getPerformedBy()->getFirstName().' '.$expense->getPerformedBy()->getLastName()) : null,
        ];
    }
}
