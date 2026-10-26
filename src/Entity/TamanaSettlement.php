<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'idx_tamana_settlement_station_date', columns: ['station_id', 'settlement_date'])]
class TamanaSettlement
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Stations $station = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] private ?User $performedBy = null;
    #[ORM\Column(length: 20)] private string $type = 'BANK_DEPOSIT';
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)] private string $amount = '0';
    #[ORM\Column(type: Types::DATE_IMMUTABLE)] private ?\DateTimeImmutable $settlementDate = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $reference = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $note = null;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)] private ?\DateTimeImmutable $createdAt = null;
    public function getId(): ?int { return $this->id; }
    public function getStation(): ?Stations { return $this->station; }
    public function setStation(Stations $v): static { $this->station = $v; return $this; }
    public function getPerformedBy(): ?User { return $this->performedBy; }
    public function setPerformedBy(?User $v): static { $this->performedBy = $v; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $v): static { $this->type = $v; return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $v): static { $this->amount = $v; return $this; }
    public function getSettlementDate(): ?\DateTimeImmutable { return $this->settlementDate; }
    public function setSettlementDate(\DateTimeImmutable $v): static { $this->settlementDate = $v; return $this; }
    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $v): static { $this->reference = $v; return $this; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $v): static { $this->note = $v; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
}
