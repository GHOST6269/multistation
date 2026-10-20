<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'fuel_attendant_assignment')]
#[ORM\Index(name: 'idx_attendant_assignment_station', columns: ['station_id', 'assigned_at'])]
#[ORM\Index(name: 'idx_attendant_assignment_nozzle_active', columns: ['nozzle_id', 'removed_at'])]
class FuelAttendantAssignment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Stations $station = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?FuelNozzle $nozzle = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PumpAttendant $attendant = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $assignedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $removedAt = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedBy = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $removedBy = null;

    public function getId(): ?int { return $this->id; }
    public function getStation(): ?Stations { return $this->station; }
    public function setStation(Stations $value): static { $this->station = $value; return $this; }
    public function getNozzle(): ?FuelNozzle { return $this->nozzle; }
    public function setNozzle(FuelNozzle $value): static { $this->nozzle = $value; return $this; }
    public function getAttendant(): ?PumpAttendant { return $this->attendant; }
    public function setAttendant(PumpAttendant $value): static { $this->attendant = $value; return $this; }
    public function getAssignedAt(): ?\DateTimeImmutable { return $this->assignedAt; }
    public function setAssignedAt(?\DateTimeImmutable $value): static { $this->assignedAt = $value; return $this; }
    public function getRemovedAt(): ?\DateTimeImmutable { return $this->removedAt; }
    public function setRemovedAt(?\DateTimeImmutable $value): static { $this->removedAt = $value; return $this; }
    public function getAssignedBy(): ?User { return $this->assignedBy; }
    public function setAssignedBy(?User $value): static { $this->assignedBy = $value; return $this; }
    public function getRemovedBy(): ?User { return $this->removedBy; }
    public function setRemovedBy(?User $value): static { $this->removedBy = $value; return $this; }
}
