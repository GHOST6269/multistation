<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'idx_tamana_tx_station_date', columns: ['station_id', 'transaction_date'])]
class TamanaTransaction
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Stations $station = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?TamanaCard $card = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] private ?User $performedBy = null;
    #[ORM\Column(length: 20)] private string $type = 'TOP_UP';
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)] private string $amount = '0';
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)] private string $balanceAfter = '0';
    #[ORM\Column(type: Types::DATE_IMMUTABLE)] private ?\DateTimeImmutable $transactionDate = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $reference = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $note = null;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)] private ?\DateTimeImmutable $createdAt = null;
    public function getId(): ?int { return $this->id; }
    public function getStation(): ?Stations { return $this->station; }
    public function setStation(Stations $v): static { $this->station = $v; return $this; }
    public function getCard(): ?TamanaCard { return $this->card; }
    public function setCard(TamanaCard $v): static { $this->card = $v; return $this; }
    public function getPerformedBy(): ?User { return $this->performedBy; }
    public function setPerformedBy(?User $v): static { $this->performedBy = $v; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $v): static { $this->type = $v; return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $v): static { $this->amount = $v; return $this; }
    public function getBalanceAfter(): string { return $this->balanceAfter; }
    public function setBalanceAfter(string $v): static { $this->balanceAfter = $v; return $this; }
    public function getTransactionDate(): ?\DateTimeImmutable { return $this->transactionDate; }
    public function setTransactionDate(\DateTimeImmutable $v): static { $this->transactionDate = $v; return $this; }
    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $v): static { $this->reference = $v; return $this; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $v): static { $this->note = $v; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
}
