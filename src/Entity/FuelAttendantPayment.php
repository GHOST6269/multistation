<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'idx_attendant_payment_day', columns: ['station_id', 'attendant_id', 'reading_date'])]
#[ORM\Index(name: 'idx_attendant_payment_date', columns: ['station_id', 'payment_date'])]
class FuelAttendantPayment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    private ?Stations $station = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PumpAttendant $attendant = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FuelPaymentMethod $paymentMethod = null;

    #[ORM\Column(length: 100)]
    private string $paymentMethodName = '';

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Customer $customer = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $paymentDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $readingDate = null;

    #[ORM\Column(length: 120)]
    private string $responsible = '';

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $enteredBy = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $amount = '0';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function getStation(): ?Stations { return $this->station; }
    public function setStation(Stations $station): static { $this->station = $station; return $this; }
    public function getAttendant(): ?PumpAttendant { return $this->attendant; }
    public function setAttendant(PumpAttendant $attendant): static { $this->attendant = $attendant; return $this; }
    public function getPaymentMethod(): ?FuelPaymentMethod { return $this->paymentMethod; }
    public function setPaymentMethod(?FuelPaymentMethod $paymentMethod): static { $this->paymentMethod = $paymentMethod; return $this; }
    public function getPaymentMethodName(): string { return $this->paymentMethodName; }
    public function setPaymentMethodName(string $name): static { $this->paymentMethodName = $name; return $this; }
    public function getCustomer(): ?Customer { return $this->customer; }
    public function setCustomer(?Customer $customer): static { $this->customer = $customer; return $this; }
    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): static { $this->reference = $reference; return $this; }
    public function getPaymentDate(): ?\DateTimeImmutable { return $this->paymentDate; }
    public function setPaymentDate(\DateTimeImmutable $date): static { $this->paymentDate = $date; return $this; }
    public function getReadingDate(): ?\DateTimeImmutable { return $this->readingDate; }
    public function setReadingDate(\DateTimeImmutable $date): static { $this->readingDate = $date; return $this; }
    public function getResponsible(): string { return $this->responsible; }
    public function setResponsible(string $responsible): static { $this->responsible = $responsible; return $this; }
    public function getEnteredBy(): ?User { return $this->enteredBy; }
    public function setEnteredBy(?User $user): static { $this->enteredBy = $user; return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
