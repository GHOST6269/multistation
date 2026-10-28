<?php

namespace App\Controller;

use App\Entity\CustomerPayment;
use App\Entity\Expense;
use App\Entity\FuelPaymentMethod;
use App\Entity\FuelShiftReading;
use App\Entity\FuelAttendantPayment;
use App\Entity\Stations;
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/journals')]
final class JournalController extends AbstractController
{
    private const TYPES = ['CASH', 'BANK', 'CREDIT', 'SUPPLIER'];

    #[Route('', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $station = $em->getRepository(Stations::class)->find((int) $request->query->get('station'));
        $type = strtoupper((string) $request->query->get('type'));
        if (!$station || !$access->canAccessStation($station)) return $access->denyStation();
        if (!in_array($type, self::TYPES, true)) return $this->json(['message' => 'Type de journal invalide'], 422);

        $methods = $em->getRepository(FuelPaymentMethod::class)->findBy(['station' => $station]);
        $methodsById = []; $methodsByCode = [];
        foreach ($methods as $method) { $methodsById[$method->getId()] = $method; $methodsByCode[$method->getCode()] = $method; }
        $rows = [];
        $add = static function (string $date, string $label, string $detail, float $amount, string $direction, ?string $reference = null, ?string $kind = null, ?int $customerId = null, ?string $customerName = null) use (&$rows): void {
            $rows[] = ['date' => $date, 'label' => $label, 'detail' => $detail, 'amount' => round($amount, 2), 'direction' => $direction, 'reference' => $reference, 'kind' => $kind, 'customerId' => $customerId, 'customerName' => $customerName];
        };

        if (in_array($type, ['CASH', 'BANK', 'CREDIT', 'SUPPLIER'], true)) {
            foreach ($em->getRepository(FuelShiftReading::class)->findBy(['station' => $station]) as $reading) {
                foreach ($reading->getPayments() as $line) {
                    $lineType = $this->lineType($line, $methodsById, $methodsByCode);
                    if ($lineType !== $type) continue;
                    $isCredit = $type === 'CREDIT';
                    $isCreditSale = $isCredit;
                    $add((string) ($line['date'] ?? $reading->getWorkDate()?->format('Y-m-d') ?? ''), $isCreditSale ? 'Vente à crédit' : ($type === 'SUPPLIER' ? 'Prélèvement fournisseur' : 'Vente carburant'), trim(($reading->getInvoiceNumber() ?? 'Relevé #'.$reading->getId()).' · '.($line['label'] ?? $line['type'] ?? '')), (float) ($line['amount'] ?? 0), ($isCredit || $type === 'SUPPLIER') ? 'DEBIT' : 'IN', $line['reference'] ?? null, $isCreditSale ? 'SALE' : null, $isCreditSale ? (int) ($line['customerId'] ?? $reading->getCustomer()?->getId() ?? 0) ?: null : null, $isCreditSale ? (string) ($line['customerName'] ?? $reading->getCustomer()?->getName() ?? '') ?: null : null);
                }
            }
        }

        if ($type === 'CASH' || $type === 'BANK') {
            foreach ($em->getRepository(FuelAttendantPayment::class)->findBy(['station' => $station]) as $payment) {
                $method = $payment->getPaymentMethod();
                if (!$method || $this->methodType($method) !== $type) continue;
                $add($payment->getPaymentDate()?->format('Y-m-d') ?? '', 'Versement pompiste', $payment->getResponsible().' · '.$payment->getPaymentMethodName(), (float) $payment->getAmount(), 'IN', $payment->getReference());
            }
            if ($type === 'BANK') {
                foreach ($em->getRepository(CustomerPayment::class)->findBy(['station' => $station]) as $payment) {
                    if ($this->codeType($payment->getPaymentMethod(), $methodsByCode) !== 'BANK') continue;
                    $add($payment->getPaymentDate()?->format('Y-m-d') ?? '', 'Règlement client', $payment->getCustomer()?->getName() ?? 'Compte client', (float) $payment->getAmount(), 'IN', $payment->getReference());
                }
            }
        }

        foreach ($em->getRepository(Expense::class)->findBy(['station' => $station]) as $expense) {
            if ($this->codeType($expense->getPaymentMethod(), $methodsByCode) !== $type) continue;
            $detail = $expense->getDescription();
            if ($expense->getSupplier()) $detail .= ' · Fournisseur : '.$expense->getSupplier()->getName();
            $direction = $type === 'CREDIT' ? 'CREDIT' : 'OUT';
            $add($expense->getExpenseDate()?->format('Y-m-d') ?? '', 'Dépense · '.$expense->getCategory(), $detail, (float) $expense->getAmount(), $direction, $expense->getReference());
        }

        if ($type === 'CREDIT') {
            foreach ($em->getRepository(FuelAttendantPayment::class)->findBy(['station' => $station]) as $payment) {
                if (!($payment->getPaymentMethod()?->isCredit() ?? false)) continue;
                $add($payment->getPaymentDate()?->format('Y-m-d') ?? '', 'Vente à crédit', $payment->getResponsible().' · '.$payment->getPaymentMethodName(), (float) $payment->getAmount(), 'DEBIT', $payment->getReference(), 'SALE', $payment->getCustomer()?->getId(), $payment->getCustomer()?->getName());
            }
            foreach ($em->getRepository(CustomerPayment::class)->findBy(['station' => $station]) as $payment) {
                $add($payment->getPaymentDate()?->format('Y-m-d') ?? '', 'Règlement de crédit', $payment->getCustomer()?->getName() ?? 'Compte client', (float) $payment->getAmount(), 'CREDIT', $payment->getReference(), 'PAYMENT', $payment->getCustomer()?->getId(), $payment->getCustomer()?->getName());
            }
        }

        if ($type === 'SUPPLIER') {
            foreach ($em->getRepository(CustomerPayment::class)->findBy(['station' => $station]) as $payment) {
                if ($this->codeType($payment->getPaymentMethod(), $methodsByCode) !== 'SUPPLIER') continue;
                $add($payment->getPaymentDate()?->format('Y-m-d') ?? '', 'Prélèvement fournisseur', $payment->getCustomer()?->getName() ?? 'Règlement client', (float) $payment->getAmount(), 'DEBIT', $payment->getReference());
            }
            foreach ($em->getRepository(FuelAttendantPayment::class)->findBy(['station' => $station]) as $payment) {
                if (!$payment->getPaymentMethod()?->isSupplierDeduction()) continue;
                $add($payment->getPaymentDate()?->format('Y-m-d') ?? '', 'Prélèvement fournisseur', $payment->getResponsible(), (float) $payment->getAmount(), 'DEBIT', $payment->getReference());
            }
        }

        $from = (string) $request->query->get('from', ''); $to = (string) $request->query->get('to', '');
        $rows = array_values(array_filter($rows, static fn(array $row): bool => (!$from || $row['date'] >= $from) && (!$to || $row['date'] <= $to)));
        usort($rows, static fn(array $a, array $b): int => strcmp($b['date'], $a['date']));
        $debitDirection = $type === 'CREDIT' ? 'DEBIT' : ($type === 'SUPPLIER' ? 'DEBIT' : 'IN');
        $creditDirection = $type === 'CREDIT' ? 'CREDIT' : 'OUT';
        $incoming = array_sum(array_map(static fn(array $row): float => $row['direction'] === $debitDirection ? $row['amount'] : 0.0, $rows));
        $outgoing = array_sum(array_map(static fn(array $row): float => $row['direction'] === $creditDirection ? $row['amount'] : 0.0, $rows));
        return $this->json(['rows' => $rows, 'count' => count($rows), 'incoming' => round($incoming, 2), 'outgoing' => round($outgoing, 2), 'balance' => round($incoming - $outgoing, 2)]);
    }

    private function lineType(array $line, array $methodsById, array $methodsByCode): string
    {
        if (!empty($line['isCredit']) || ($line['type'] ?? '') === 'CLIENT_VOUCHER') return 'CREDIT';
        if (!empty($line['supplierDeduction'])) return 'SUPPLIER';
        $type = strtoupper((string) ($line['journalType'] ?? ''));
        if (in_array($type, self::TYPES, true)) return $type;
        $method = isset($line['methodId']) ? ($methodsById[(int) $line['methodId']] ?? null) : ($methodsByCode[(string) ($line['type'] ?? '')] ?? null);
        return $method ? $this->methodType($method) : $this->codeType((string) ($line['type'] ?? ''), $methodsByCode);
    }

    private function codeType(string $code, array $methodsByCode): string
    {
        $method = $methodsByCode[$code] ?? null;
        if ($method) return $this->methodType($method);
        return in_array(strtoupper($code), ['CASH', 'ESPECES', 'ESPECE'], true) ? 'CASH' : 'BANK';
    }

    private function methodType(FuelPaymentMethod $method): string
    {
        if ($method->isCredit()) return 'CREDIT';
        if ($method->isSupplierDeduction()) return 'SUPPLIER';
        $type = strtoupper($method->getJournalType());
        return in_array($type, self::TYPES, true) ? $type : 'BANK';
    }
}
