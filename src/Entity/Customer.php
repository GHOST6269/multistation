<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Customer
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)] private ?Stations $station = null;
    #[ORM\Column(length: 30, nullable: true)] private ?string $code = null;
    #[ORM\Column(length: 150)] private string $name = '';
    #[ORM\Column(length: 100, nullable: true)] private ?string $contactPerson = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $phone = null;
    #[ORM\Column(length: 180, nullable: true)] private ?string $email = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $address = null;
    #[ORM\Column(options: ['default' => true])] private bool $isActive = true;
    #[ORM\Column] private ?\DateTimeImmutable $createdAt = null;
    public function getId(): ?int { return $this->id; }
    public function getStation(): ?Stations { return $this->station; }
    public function setStation(Stations $value): static { $this->station = $value; return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $value): static { $this->code = $value; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $value): static { $this->name = $value; return $this; }
    public function getContactPerson(): ?string { return $this->contactPerson; }
    public function setContactPerson(?string $value): static { $this->contactPerson = $value; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $value): static { $this->phone = $value; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $value): static { $this->email = $value; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $value): static { $this->address = $value; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $value): static { $this->isActive = $value; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $value): static { $this->createdAt = $value; return $this; }
}
