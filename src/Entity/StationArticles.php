<?php

namespace App\Entity;

use App\Repository\StationArticlesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StationArticlesRepository::class)]
class StationArticles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stationArticles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stations $station = null;

    #[ORM\ManyToOne(inversedBy: 'stationArticles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Articles $article = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $currentSockBase = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 6)]
    private ?string $minimumStockBase = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, StationArticleUnits>
     */
    #[ORM\OneToMany(targetEntity: StationArticleUnits::class, mappedBy: 'stationArticle')]
    private Collection $stationArticleUnits;

    public function __construct()
    {
        $this->stationArticleUnits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStation(): ?Stations
    {
        return $this->station;
    }

    public function setStation(?Stations $station): static
    {
        $this->station = $station;

        return $this;
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

    public function getCurrentSockBase(): ?string
    {
        return $this->currentSockBase;
    }

    public function setCurrentSockBase(string $currentSockBase): static
    {
        $this->currentSockBase = $currentSockBase;

        return $this;
    }

    public function getMinimumStockBase(): ?string
    {
        return $this->minimumStockBase;
    }

    public function setMinimumStockBase(string $minimumStockBase): static
    {
        $this->minimumStockBase = $minimumStockBase;

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
            $stationArticleUnit->setStationArticle($this);
        }

        return $this;
    }

    public function removeStationArticleUnit(StationArticleUnits $stationArticleUnit): static
    {
        if ($this->stationArticleUnits->removeElement($stationArticleUnit)) {
            // set the owning side to null (unless already changed)
            if ($stationArticleUnit->getStationArticle() === $this) {
                $stationArticleUnit->setStationArticle(null);
            }
        }

        return $this;
    }
}
