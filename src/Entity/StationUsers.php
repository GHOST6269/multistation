<?php

namespace App\Entity;

use App\Repository\StationUsersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StationUsersRepository::class)]
class StationUsers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stationUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stations $station = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTime $assignedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStation(): ?Stations
    {
        return $this->station;
    }

    public function setStation(?Stations $station): static
    {
        $this->station = $station;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getAssignedAt(): ?\DateTime
    {
        return $this->assignedAt;
    }

    public function setAssignedAt(\DateTime $assignedAt): static
    {
        $this->assignedAt = $assignedAt;

        return $this;
    }
}
