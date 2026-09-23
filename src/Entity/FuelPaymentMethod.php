<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_payment_method_station_code', columns: ['station_id', 'code'])]
class FuelPaymentMethod
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    private ?Stations $station = null;

    #[ORM\Column(length: 40)]
    private string $code = '';

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $supplierDeduction = false;

    #[ORM\Column]
    private array $allowedRoles = ['ROLE_GERANT'];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function getStation(): ?Stations { return $this->station; }
    public function setStation(Stations $station): static { $this->station = $station; return $this; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $active): static { $this->isActive = $active; return $this; }
    public function isSupplierDeduction(): bool { return $this->supplierDeduction; }
    public function setSupplierDeduction(bool $enabled): static { $this->supplierDeduction = $enabled; return $this; }
    public function getAllowedRoles(): array { return $this->allowedRoles; }
    public function setAllowedRoles(array $roles): static { $this->allowedRoles = array_values($roles); return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function canBeUsedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return (bool) array_intersect($this->allowedRoles, $user->getRoles());
    }
}
