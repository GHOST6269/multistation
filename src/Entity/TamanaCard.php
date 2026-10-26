<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_tamana_card_station_number', columns: ['station_id', 'card_number'])]
class TamanaCard
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Stations $station = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] private ?Customer $customer = null;
    #[ORM\Column(name: 'card_number', length: 80)] private string $cardNumber = '';
    #[ORM\Column(length: 150)] private string $holderName = '';
    #[ORM\Column(length: 30)] private string $status = 'ACTIVE';
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)] private ?\DateTimeImmutable $createdAt = null;
    public function getId(): ?int { return $this->id; }
    public function getStation(): ?Stations { return $this->station; }
    public function setStation(Stations $v): static { $this->station = $v; return $this; }
    public function getCustomer(): ?Customer { return $this->customer; }
    public function setCustomer(?Customer $v): static { $this->customer = $v; return $this; }
    public function getCardNumber(): string { return $this->cardNumber; }
    public function setCardNumber(string $v): static { $this->cardNumber = $v; return $this; }
    public function getHolderName(): string { return $this->holderName; }
    public function setHolderName(string $v): static { $this->holderName = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
}
