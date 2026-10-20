<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FuelAttendantAssignment;
use App\Entity\FuelNozzle;
use App\Entity\PumpAttendant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class FuelAttendantAssignmentService
{
    public function change(EntityManagerInterface $em, FuelNozzle $nozzle, ?PumpAttendant $attendant, ?User $user): void
    {
        $previous = $nozzle->getAttendant();
        if ($previous?->getId() === $attendant?->getId()) return;

        $now = new \DateTimeImmutable();
        $active = $em->getRepository(FuelAttendantAssignment::class)->findOneBy(
            ['nozzle' => $nozzle, 'removedAt' => null],
            ['id' => 'DESC'],
        );

        if ($active) {
            $active->setRemovedAt($now)->setRemovedBy($user);
        } elseif ($previous) {
            // Preserve the removal event for assignments created before tracking existed.
            $em->persist((new FuelAttendantAssignment())
                ->setStation($nozzle->getPump()->getStation())
                ->setNozzle($nozzle)
                ->setAttendant($previous)
                ->setRemovedAt($now)
                ->setRemovedBy($user));
        }

        if ($attendant) {
            $em->persist((new FuelAttendantAssignment())
                ->setStation($nozzle->getPump()->getStation())
                ->setNozzle($nozzle)
                ->setAttendant($attendant)
                ->setAssignedAt($now)
                ->setAssignedBy($user));
        }

        $nozzle->setAttendant($attendant);
    }
}
