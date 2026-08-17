<?php

namespace App\Entity;

use App\Repository\ArticlesUnitsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticlesUnitsRepository::class)]
class ArticlesUnits
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'articlesUnits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Articles $article = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $converstionFactor = null;

    #[ORM\Column]
    private ?bool $isBaseUnit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $barcode = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\ManyToOne(inversedBy: 'articlesUnits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Units $unit = null;

    /**
     * @var Collection<int, StationArticleUnits>
     */
    #[ORM\OneToMany(targetEntity: StationArticleUnits::class, mappedBy: 'articleUnit')]
    private Collection $stationArticleUnits;

    public function __construct()
    {
        $this->stationArticleUnits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): ?Articles
    {
        return $this->article;
    }

    public function setArticle(?Articles $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getConverstionFactor(): ?string
    {
        return $this->converstionFactor;
    }

    public function setConverstionFactor(string $converstionFactor): static
    {
        $this->converstionFactor = $converstionFactor;

        return $this;
    }

    public function isBaseUnit(): ?bool
    {
        return $this->isBaseUnit;
    }

    public function setIsBaseUnit(bool $isBaseUnit): static
    {
        $this->isBaseUnit = $isBaseUnit;

        return $this;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function setBarcode(?string $barcode): static
    {
        $this->barcode = $barcode;

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

    public function getUnit(): ?Units
    {
        return $this->unit;
    }

    public function setUnit(?Units $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return Collection<int, StationArticleUnits>
     */
    public function getStationArticleUnits(): Collection
    {
        return $this->stationArticleUnits;
    }

    public function addStationArticleUnit(StationArticleUnits $stationArticleUnit): static
    {
        if (!$this->stationArticleUnits->contains($stationArticleUnit)) {
            $this->stationArticleUnits->add($stationArticleUnit);
            $stationArticleUnit->setArticleUnit($this);
        }

        return $this;
    }

    public function removeStationArticleUnit(StationArticleUnits $stationArticleUnit): static
    {
        if ($this->stationArticleUnits->removeElement($stationArticleUnit)) {
            // set the owning side to null (unless already changed)
            if ($stationArticleUnit->getArticleUnit() === $this) {
                $stationArticleUnit->setArticleUnit(null);
            }
        }

        return $this;
    }
}
