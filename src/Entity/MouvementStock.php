<?php

namespace App\Entity;

use App\Repository\MouvementStockRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementStockRepository::class)]
#[ORM\Index(name: 'IDX_MOVEMENT_REFERENCE', columns: ['reference'])]
class MouvementStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?StationArticles $stationArticle = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ArticlesUnits $articleUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $enteredQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $conversionFactor = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $baseQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $previousStockBase = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $newStockBase = null;

    #[ORM\Column(length: 255)]
    private ?string $mouvementType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(nullable: true)]
    private ?array $details = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStationArticle(): ?StationArticles
    {
        return $this->stationArticle;
    }

    public function setStationArticle(?StationArticles $stationArticle): static
    {
        $this->stationArticle = $stationArticle;

        return $this;
    }

    public function getArticleUnit(): ?ArticlesUnits
    {
        return $this->articleUnit;
    }

    public function setArticleUnit(?ArticlesUnits $articleUnit): static
    {
        $this->articleUnit = $articleUnit;

        return $this;
    }

    public function getEnteredQuantity(): ?string
    {
        return $this->enteredQuantity;
    }

    public function setEnteredQuantity(string $enteredQuantity): static
    {
        $this->enteredQuantity = $enteredQuantity;

        return $this;
    }

    public function getConversionFactor(): ?string
    {
        return $this->conversionFactor;
    }

    public function setConversionFactor(string $conversionFactor): static
    {
        $this->conversionFactor = $conversionFactor;

        return $this;
    }

    public function getBaseQuantity(): ?string
    {
        return $this->baseQuantity;
    }

    public function setBaseQuantity(string $baseQuantity): static
    {
        $this->baseQuantity = $baseQuantity;

        return $this;
    }

    public function getPreviousStockBase(): ?string
    {
        return $this->previousStockBase;
    }

    public function setPreviousStockBase(string $previousStockBase): static
    {
        $this->previousStockBase = $previousStockBase;

        return $this;
    }

    public function getNewStockBase(): ?string
    {
        return $this->newStockBase;
    }

    public function setNewStockBase(string $newStockBase): static
    {
        $this->newStockBase = $newStockBase;

        return $this;
    }

    public function getMouvementType(): ?string
    {
        return $this->mouvementType;
    }

    public function setMouvementType(string $mouvementType): static
    {
        $this->mouvementType = $mouvementType;

        return $this;
    }

    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $reason): static { $this->reason = $reason; return $this; }
    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): static { $this->reference = $reference; return $this; }
    public function getDetails(): ?array { return $this->details; }
    public function setDetails(?array $details): static { $this->details = $details; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
