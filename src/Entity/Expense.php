<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Expense
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    private ?Stations $station = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    private ?User $performedBy = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Supplier $supplier = null;
    #[ORM\Column(length: 80)]
    private string $category = 'Autre';
    #[ORM\Column(length: 255)]
    private string $description = '';
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $amount = '0';
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $expenseDate = null;
    #[ORM\Column(length: 30)]
    private string $paymentMethod = 'CASH';
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $reference = null;
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function getStation(): ?Stations { return $this->station; }
    public function setStation(Stations $value): static { $this->station = $value; return $this; }
    public function getPerformedBy(): ?User { return $this->performedBy; }
    public function setPerformedBy(User $value): static { $this->performedBy = $value; return $this; }
    public function getSupplier(): ?Supplier { return $this->supplier; }
    public function setSupplier(?Supplier $value): static { $this->supplier = $value; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $value): static { $this->category = $value; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $value): static { $this->description = $value; return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $value): static { $this->amount = $value; return $this; }
    public function getExpenseDate(): ?\DateTimeImmutable { return $this->expenseDate; }
    public function setExpenseDate(\DateTimeImmutable $value): static { $this->expenseDate = $value; return $this; }
    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function setPaymentMethod(string $value): static { $this->paymentMethod = $value; return $this; }
    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $value): static { $this->reference = $value; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $value): static { $this->createdAt = $value; return $this; }
}
