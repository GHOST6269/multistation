<?php

namespace App\Entity;

use App\Repository\StationArticleUnitsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StationArticleUnitsRepository::class)]
class StationArticleUnits
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stationArticleUnits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StationArticles $stationArticle = null;

    #[ORM\ManyToOne(inversedBy: 'stationArticleUnits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ArticlesUnits $articleUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $purchasePrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $salePrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $wholesalePrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $minimumSalePrice = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTime $creatAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt = null;

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

    public function getPurchasePrice(): ?string
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(?string $purchasePrice): static
    {
        $this->purchasePrice = $purchasePrice;

        return $this;
    }

    public function getSalePrice(): ?string
    {
        return $this->salePrice;
    }

    public function setSalePrice(string $salePrice): static
    {
        $this->salePrice = $salePrice;

        return $this;
    }

    public function getWholesalePrice(): ?string
    {
        return $this->wholesalePrice;
    }

    public function setWholesalePrice(string $wholesalePrice): static
    {
        $this->wholesalePrice = $wholesalePrice;

        return $this;
    }

    public function getMinimumSalePrice(): ?string
    {
        return $this->minimumSalePrice;
    }

    public function setMinimumSalePrice(string $minimumSalePrice): static
    {
        $this->minimumSalePrice = $minimumSalePrice;

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

    public function getCreatAt(): ?\DateTime
    {
        return $this->creatAt;
    }

    public function setCreatAt(\DateTime $creatAt): static
    {
        $this->creatAt = $creatAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
