<?php

namespace App\Entity;

use App\Repository\ShopSaleItemsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopSaleItemsRepository::class)]
class ShopSaleItems
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
    private ?StationArticleUnits $stationArticleUnit = null;

    #[ORM\Column(length: 255)]
    private ?string $articleNameSnapshot = null;

    #[ORM\Column(length: 255)]
    private ?string $unitNameSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $conversionFactorSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $baseQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $unitPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $unitCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $discountAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $lineTotal = null;

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

    public function getStationArticleUnit(): ?StationArticleUnits
    {
        return $this->stationArticleUnit;
    }

    public function setStationArticleUnit(?StationArticleUnits $stationArticleUnit): static
    {
        $this->stationArticleUnit = $stationArticleUnit;

        return $this;
    }

    public function getArticleNameSnapshot(): ?string
    {
        return $this->articleNameSnapshot;
    }

    public function setArticleNameSnapshot(string $articleNameSnapshot): static
    {
        $this->articleNameSnapshot = $articleNameSnapshot;

        return $this;
    }

    public function getUnitNameSnapshot(): ?string
    {
        return $this->unitNameSnapshot;
    }

    public function setUnitNameSnapshot(string $unitNameSnapshot): static
    {
        $this->unitNameSnapshot = $unitNameSnapshot;

        return $this;
    }

    public function getConversionFactorSnapshot(): ?string
    {
        return $this->conversionFactorSnapshot;
    }

    public function setConversionFactorSnapshot(string $conversionFactorSnapshot): static
    {
        $this->conversionFactorSnapshot = $conversionFactorSnapshot;

        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;

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

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getUnitCost(): ?string
    {
        return $this->unitCost;
    }

    public function setUnitCost(?string $unitCost): static
    {
        $this->unitCost = $unitCost;

        return $this;
    }

    public function getDiscountAmount(): ?string
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?string $discountAmount): static
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    public function getLineTotal(): ?string
    {
        return $this->lineTotal;
    }

    public function setLineTotal(string $lineTotal): static
    {
        $this->lineTotal = $lineTotal;

        return $this;
    }
}
