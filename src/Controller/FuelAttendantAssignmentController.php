<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FuelAttendantAssignment;
use App\Entity\FuelNozzle;
use App\Entity\PumpAttendant;
use App\Entity\Stations;
use App\Service\FuelAttendantAssignmentService;
use App\Service\UserAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fuel/attendant-assignments')]
final class FuelAttendantAssignmentController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em, UserAccessService $access): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $station = $em->getRepository(Stations::class)->find((int) $request->query->get('station'));
        if (!$station) return $this->json(['message' => 'Station invalide'], 422);
        if (!$access->canAccessStation($station)) return $access->denyStation();

        $nozzles = array_values(array_filter(
            $em->getRepository(FuelNozzle::class)->findBy(['isActive' => true]),
            static fn (FuelNozzle $nozzle): bool => $nozzle->getPump()?->getStation()?->getId() === $station->getId(),
        ));
        $history = $em->getRepository(FuelAttendantAssignment::class)->findBy(['station' => $station], ['id' => 'DESC']);

        return $this->json([
            'attendants' => array_map(static fn (PumpAttendant $attendant): array => [
                'id' => $attendant->getId(), 'name' => $attendant->getFullName(), 'code' => $attendant->getCode(),
            ], $em->getRepository(PumpAttendant::class)->findBy(['station' => $station, 'isActive' => true], ['fullName' => 'ASC'])),
            'nozzles' => array_map(static fn (FuelNozzle $nozzle): array => [
                'id' => $nozzle->getId(), 'code' => $nozzle->getCode(),
                'pump' => $nozzle->getPump()?->getName(), 'tank' => $nozzle->getTank()?->getName(),
                'attendantId' => $nozzle->getAttendant()?->getId(), 'attendant' => $nozzle->getAttendant()?->getFullName(),
            ], $nozzles),
            'history' => array_map(static fn (FuelAttendantAssignment $item): array => [
                'id' => $item->getId(), 'nozzleId' => $item->getNozzle()?->getId(),
                'nozzle' => $item->getNozzle()?->getCode(), 'pump' => $item->getNozzle()?->getPump()?->getName(),
                'attendantId' => $item->getAttendant()?->getId(), 'attendant' => $item->getAttendant()?->getFullName(),
                'assignedAt' => $item->getAssignedAt()?->format(DATE_ATOM),
                'removedAt' => $item->getRemovedAt()?->format(DATE_ATOM),
                'assignedBy' => self::userName($item->getAssignedBy()),
                'removedBy' => self::userName($item->getRemovedBy()),
            ], $history),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function assign(Request $request, EntityManagerInterface $em, UserAccessService $access, FuelAttendantAssignmentService $assignments): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $data = $request->toArray();
        $station = $em->getRepository(Stations::class)->find((int) ($data['stationId'] ?? 0));
        $nozzle = $em->getRepository(FuelNozzle::class)->find((int) ($data['nozzleId'] ?? 0));
        $attendant = $em->getRepository(PumpAttendant::class)->find((int) ($data['attendantId'] ?? 0));
        if (!$station || !$nozzle || !$attendant || !$access->canAccessStation($station)) return $this->json(['message' => 'Station, pistolet ou pompiste invalide'], 422);
        if ($nozzle->getPump()?->getStation()?->getId() !== $station->getId() || $attendant->getStation()?->getId() !== $station->getId() || !$nozzle->isActive() || !$attendant->isActive()) return $this->json(['message' => 'Le pistolet et le pompiste doivent être actifs dans la même station'], 422);

        $assignments->change($em, $nozzle, $attendant, $access->currentUser());
        $em->flush();
        return $this->json(['message' => 'Pistolet attribué', 'attendantId' => $attendant->getId()]);
    }

    #[Route('/{nozzleId}/remove', requirements: ['nozzleId' => '\\d+'], methods: ['POST'])]
    public function remove(int $nozzleId, Request $request, EntityManagerInterface $em, UserAccessService $access, FuelAttendantAssignmentService $assignments): JsonResponse
    {
        if ($denied = $access->require([UserAccessService::ROLE_GERANT, UserAccessService::ROLE_ASSISTANT])) return $denied;
        $station = $em->getRepository(Stations::class)->find((int) $request->toArray()['stationId'] ?? 0);
        $nozzle = $em->getRepository(FuelNozzle::class)->find($nozzleId);
        if (!$station || !$nozzle || $nozzle->getPump()?->getStation()?->getId() !== $station->getId()) return $this->json(['message' => 'Station ou pistolet invalide'], 422);
        if (!$access->canAccessStation($station)) return $access->denyStation();
        if (!$nozzle->getAttendant()) return $this->json(['message' => 'Ce pistolet n’est attribué à aucun pompiste'], 422);

        $assignments->change($em, $nozzle, null, $access->currentUser());
        $em->flush();
        return $this->json(['message' => 'Attribution terminée']);
    }

    private static function userName(?\App\Entity\User $user): ?string
    {
        if (!$user) return null;
        return trim(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')) ?: $user->getEmail();
    }
}
