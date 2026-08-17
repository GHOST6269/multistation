<?php

namespace App\Controller;

use App\Entity\FuelNozzle;
use App\Entity\FuelPaymentMethod;
use App\Entity\FuelShiftReading;
use App\Entity\PumpAttendant;
use App\Entity\Stations;
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

    #[Route('/payment-methods', methods: ['GET'])]
    public function methods(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $station = $em->getRepository(Stations::class)->find((int) $request->query->get('station'));
        if (!$station) return $this->json(['message' => 'Station invalide'], 422);
        $items = $em->getRepository(FuelPaymentMethod::class)->findBy(['station' => $station], ['name' => 'ASC']);
        if (!$items) {
            foreach (self::DEFAULT_METHODS as $code => $name) {
                $item = (new FuelPaymentMethod())->setStation($station)->setCode($code)->setName($name)->setCreatedAt(new \DateTimeImmutable());
                $em->persist($item);
                $items[] = $item;
            }
            $em->flush();
        }
        return $this->json(['methods' => array_map(fn(FuelPaymentMethod $x) => $this->methodRow($x), $items)]);
    }

    #[Route('/payment-methods', methods: ['POST'])]
    public function createMethod(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();
        $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        $name = trim((string) ($data['name'] ?? ''));
        if (!$station || $name === '') return $this->json(['message' => 'Station et libellé obligatoires'], 422);
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $code = preg_replace('/[^A-Z0-9_]+/', '_', $code ?: $name) ?: 'MODE';
        if ($em->getRepository(FuelPaymentMethod::class)->findOneBy(['station' => $station, 'code' => $code])) return $this->json(['message' => 'Ce code existe déjà'], 422);
        $item = (new FuelPaymentMethod())->setStation($station)->setCode($code)->setName($name)->setCreatedAt(new \DateTimeImmutable());
        $em->persist($item); $em->flush();
        return $this->json($this->methodRow($item), 201);
    }

    #[Route('/payment-methods/{id}/deactivate', methods: ['PATCH'])]
    public function deactivateMethod(FuelPaymentMethod $method, EntityManagerInterface $em): JsonResponse
    {
        $method->setIsActive(false); $em->flush();
        return $this->json($this->methodRow($method));
    }

    #[Route('/simple-readings', methods: ['POST'])]
    public function createReading(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();
        $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        $nozzle = $em->getRepository(FuelNozzle::class)->find((int) ($data['nozzleId'] ?? 0));
        $attendant = $em->getRepository(PumpAttendant::class)->find((int) ($data['attendantId'] ?? 0));
        $method = $em->getRepository(FuelPaymentMethod::class)->find((int) ($data['paymentMethodId'] ?? 0));
        if (!$station || !$nozzle || !$attendant || !$method || !$method->isActive() || $method->getStation()?->getId() !== $station->getId() || $nozzle->getPump()?->getStation()?->getId() !== $station->getId() || $attendant->getStation()?->getId() !== $station->getId()) return $this->json(['message' => 'Références invalides'], 422);
        $start = (float) ($data['startIndex'] ?? 0); $end = (float) ($data['endIndex'] ?? 0);
        $rc = max(0, (float) ($data['returnToTank'] ?? 0)); $output = $end - $start; $sold = $output - $rc;
        $price = max(0, (float) ($data['unitPrice'] ?? $nozzle->getUnitPrice())); $total = round($sold * $price, 2);
        $paid = max(0, (float) ($data['paymentAmount'] ?? 0));
        if ($output < 0 || $sold < 0) return $this->json(['message' => 'Les index ou le RC sont incohérents'], 422);
        if (abs($paid - $total) > .01) return $this->json(['message' => sprintf('Le paiement doit être de %.2f Ar', $total)], 422);
        $tank = $nozzle->getTank();
        if ((float) $tank->getCurrentStock() < $sold) return $this->json(['message' => 'Stock cuve insuffisant'], 422);
        $payment = [['type' => $method->getCode(), 'label' => $method->getName(), 'amount' => $paid, 'reference' => trim((string) ($data['paymentReference'] ?? '')) ?: null]];
        $reading = (new FuelShiftReading())->setStation($station)->setNozzle($nozzle)->setAttendant($attendant)->setWorkDate(new \DateTimeImmutable($data['date'] ?? 'today'))->setStartIndex((string) $start)->setEndIndex((string) $end)->setReturnToTank((string) $rc)->setQuantitySold((string) $sold)->setUnitPrice((string) $price)->setTotalAmount((string) $total)->setPayments($payment)->setCreatedAt(new \DateTimeImmutable());
        $nozzle->setCurrentIndex((string) $end); $tank->setCurrentStock((string) ((float) $tank->getCurrentStock() - $sold));
        $em->persist($reading); $em->flush();
        return $this->json(['id' => $reading->getId()], 201);
    }

    #[Route('/payment-history', methods: ['GET'])]
    public function history(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $readings = $em->getRepository(FuelShiftReading::class)->findBy(['station' => (int) $request->query->get('station')], ['workDate' => 'DESC', 'id' => 'DESC'], 100);
        $rows = [];
        foreach ($readings as $reading) foreach ($reading->getPayments() as $payment) $rows[] = ['id' => $reading->getId().'-'.($payment['type'] ?? ''), 'date' => $reading->getWorkDate()?->format('Y-m-d'), 'responsible' => $reading->getAttendant()?->getFullName(), 'nozzle' => $reading->getNozzle()?->getCode(), 'method' => $payment['label'] ?? $payment['type'] ?? '—', 'reference' => $payment['reference'] ?? null, 'amount' => (float) ($payment['amount'] ?? 0)];
        return $this->json(['payments' => $rows]);
    }

    private function methodRow(FuelPaymentMethod $method): array
    {
        return ['id' => $method->getId(), 'code' => $method->getCode(), 'name' => $method->getName(), 'active' => $method->isActive()];
    }
}
