<?php

namespace App\Controller;

use App\Entity\{FuelNozzle, FuelPump, FuelTank, FuelType, PumpAttendant, Stations};
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fuel/config')]
final class FuelConfigController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL])) return $denied;
        $stationId = (int) $request->query->get('station');
        $station = $em->getRepository(Stations::class)->find($stationId);
        if (!$station) return $this->json(['message' => 'Station invalide'], 422);
        if (!$access->canAccessStation($station)) return $access->denyStation();
        $fuels = $em->getRepository(FuelType::class)->findBy([], ['code' => 'ASC']);
        $tanks = $em->getRepository(FuelTank::class)->findBy(['station' => $stationId], ['name' => 'ASC']);
        $pumps = $em->getRepository(FuelPump::class)->findBy(['station' => $stationId], ['name' => 'ASC']);
        $attendants = $em->getRepository(PumpAttendant::class)->findBy(['station' => $stationId], ['fullName' => 'ASC']);
        $nozzles = array_values(array_filter($em->getRepository(FuelNozzle::class)->findAll(), fn (FuelNozzle $nozzle) => $nozzle->getPump()?->getStation()?->getId() === $stationId));
        return $this->json([
            'fuels' => array_map(fn (FuelType $fuel) => ['id' => $fuel->getId(), 'code' => $fuel->getCode(), 'name' => $fuel->getName(), 'unitPrice' => (float) $fuel->getUnitPrice(), 'active' => $fuel->isActive()], $fuels),
            'tanks' => array_map(fn (FuelTank $tank) => ['id' => $tank->getId(), 'code' => $tank->getCode(), 'name' => $tank->getName(), 'fuel' => $tank->getFuelType()?->getCode(), 'fuelTypeId' => $tank->getFuelType()?->getId(), 'capacity' => (float) $tank->getCapacity(), 'stock' => (float) $tank->getCurrentStock(), 'minimum' => (float) $tank->getMinimumStock(), 'active' => $tank->isActive()], $tanks),
            'pumps' => array_map(fn (FuelPump $pump) => $this->pumpRow($pump, $nozzles), $pumps),
            'nozzles' => array_map(fn (FuelNozzle $nozzle) => ['id' => $nozzle->getId(), 'code' => $nozzle->getCode(), 'pump' => $nozzle->getPump()?->getName(), 'pumpId' => $nozzle->getPump()?->getId(), 'tank' => $nozzle->getTank()?->getName(), 'tankId' => $nozzle->getTank()?->getId(), 'attendantId' => $nozzle->getAttendant()?->getId() ?? 0, 'attendant' => $nozzle->getAttendant()?->getFullName(), 'currentIndex' => (float) $nozzle->getCurrentIndex(), 'unitPrice' => (float) ($nozzle->getTank()?->getFuelType()?->getUnitPrice() ?? 0), 'active' => $nozzle->isActive()], $nozzles),
            'attendants' => array_map(fn (PumpAttendant $attendant) => ['id' => $attendant->getId(), 'code' => $attendant->getCode(), 'name' => $attendant->getFullName(), 'contact' => $attendant->getContact(), 'nozzleIds' => array_values(array_map(fn (FuelNozzle $nozzle) => $nozzle->getId(), array_filter($nozzles, fn (FuelNozzle $nozzle) => $nozzle->getAttendant()?->getId() === $attendant->getId()))), 'nozzles' => array_map(fn (FuelNozzle $nozzle) => $nozzle->getCode(), array_values(array_filter($nozzles, fn (FuelNozzle $nozzle) => $nozzle->getAttendant()?->getId() === $attendant->getId()))), 'active' => $attendant->isActive()], $attendants),
        ]);
    }

    #[Route('/{type}/{id}', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(string $type, int $id, Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL])) return $denied;
        $data = $request->toArray(); $entity = $this->find($type, $id, $em);
        if (!$entity) return $this->json(['message' => 'Élément introuvable'], 404);
        $station = $this->stationOf($entity); if ($station && !$access->canAccessStation($station)) return $access->denyStation();
        $code = trim((string) ($data['code'] ?? '')); $name = trim((string) ($data['name'] ?? ''));
        if ($entity instanceof FuelType) $entity->setCode($code)->setName($name)->setUnitPrice((string) max(0, (float) ($data['unitPrice'] ?? 0)));
        elseif ($entity instanceof FuelTank) { $fuel = $em->getRepository(FuelType::class)->find((int) ($data['fuelTypeId'] ?? 0)); if (!$fuel) return $this->json(['message' => 'Carburant invalide'], 422); $entity->setCode($code)->setName($name)->setFuelType($fuel)->setCapacity((string) max(0, (float) ($data['capacity'] ?? 0)))->setMinimumStock((string) max(0, (float) ($data['minimumStock'] ?? 0))); }
        elseif ($entity instanceof FuelPump) $entity->setCode($code)->setName($name);
        elseif ($entity instanceof FuelNozzle) { $pump = $em->getRepository(FuelPump::class)->find((int) ($data['pumpId'] ?? 0)); $tank = $em->getRepository(FuelTank::class)->find((int) ($data['tankId'] ?? 0)); if (!$pump || !$tank || !$access->canAccessStation($pump->getStation()) || !$access->canAccessStation($tank->getStation()) || $pump->getStation()?->getId() !== $tank->getStation()?->getId()) return $this->json(['message' => 'Pompe ou cuve invalide'], 422); $attendantId = (int) ($data['attendantId'] ?? 0); $attendant = $attendantId ? $em->getRepository(PumpAttendant::class)->find($attendantId) : null; if ($attendantId && (!$attendant || $attendant->getStation()?->getId() !== $pump->getStation()?->getId())) return $this->json(['message' => 'Pompiste invalide'], 422); $entity->setCode($code)->setPump($pump)->setTank($tank)->setAttendant($attendant)->setCurrentIndex((string) max(0, (float) ($data['currentIndex'] ?? 0))); }
        elseif ($entity instanceof PumpAttendant) { $entity->setCode($code ?: null)->setFullName($name)->setContact(trim((string) ($data['contact'] ?? '')) ?: null); $selected = array_values(array_unique(array_map('intval', $data['nozzleIds'] ?? []))); foreach ($em->getRepository(FuelNozzle::class)->findAll() as $nozzle) { if ($nozzle->getPump()?->getStation()?->getId() === $entity->getStation()?->getId() && $nozzle->getAttendant()?->getId() === $entity->getId() && !in_array($nozzle->getId(), $selected, true)) $nozzle->setAttendant(null); } foreach ($selected as $nozzleId) { $nozzle = $em->getRepository(FuelNozzle::class)->find($nozzleId); if (!$nozzle || $nozzle->getPump()?->getStation()?->getId() !== $entity->getStation()?->getId()) return $this->json(['message' => 'Un pistolet sélectionné est invalide'], 422); $nozzle->setAttendant($entity); } }
        $em->flush(); return $this->json(['id' => $id]);
    }

    #[Route('/{type}/{id}/deactivate', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function deactivate(string $type, int $id, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_QUALITY_MARSHALL])) return $denied;
        $entity = $this->find($type, $id, $em); if (!$entity) return $this->json(['message' => 'Élément introuvable'], 404);
        $station = $this->stationOf($entity); if ($station && !$access->canAccessStation($station)) return $access->denyStation();
        $entity->setIsActive(false); $em->flush(); return $this->json(['id' => $id, 'active' => false]);
    }

    private function find(string $type, int $id, EntityManagerInterface $em): object|null { $class = match ($type) { 'fuel' => FuelType::class, 'tank' => FuelTank::class, 'pump' => FuelPump::class, 'nozzle' => FuelNozzle::class, 'attendant' => PumpAttendant::class, default => null }; return $class ? $em->getRepository($class)->find($id) : null; }
    private function pumpRow(FuelPump $pump, array $nozzles): array { $pumpNozzles = array_values(array_filter($nozzles, fn (FuelNozzle $nozzle) => $nozzle->getPump()?->getId() === $pump->getId())); return ['id' => $pump->getId(), 'code' => $pump->getCode(), 'name' => $pump->getName(), 'nozzleCount' => count($pumpNozzles), 'nozzles' => array_map(fn (FuelNozzle $nozzle) => ['id' => $nozzle->getId(), 'code' => $nozzle->getCode(), 'tankId' => $nozzle->getTank()?->getId(), 'tank' => $nozzle->getTank()?->getName(), 'fuel' => $nozzle->getTank()?->getFuelType()?->getCode()], $pumpNozzles), 'active' => $pump->isActive()]; }
    private function stationOf(object $entity): ?Stations { return match (true) { $entity instanceof FuelTank => $entity->getStation(), $entity instanceof FuelPump => $entity->getStation(), $entity instanceof FuelNozzle => $entity->getPump()?->getStation(), $entity instanceof PumpAttendant => $entity->getStation(), default => null }; }
}
