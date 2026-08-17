<?php

namespace App\Entity;

use App\Repository\UnitsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnitsRepository::class)]
class Units
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $symbol = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, ArticlesUnits>
     */
    #[ORM\OneToMany(targetEntity: ArticlesUnits::class, mappedBy: 'unit')]
    private Collection $articlesUnits;

    public function __construct()
    {
        $this->articlesUnits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(?string $symbol): static
    {
        $this->symbol = $symbol;

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
     * @return Collection<int, ArticlesUnits>
     */
    public function getArticlesUnits(): Collection
    {
        return $this->articlesUnits;
    }

    public function addArticlesUnit(ArticlesUnits $articlesUnit): static
    {
        if (!$this->articlesUnits->contains($articlesUnit)) {
            $this->articlesUnits->add($articlesUnit);
            $articlesUnit->setUnit($this);
        }

        return $this;
    }

    public function removeArticlesUnit(ArticlesUnits $articlesUnit): static
    {
        if ($this->articlesUnits->removeElement($articlesUnit)) {
            // set the owning side to null (unless already changed)
            if ($articlesUnit->getUnit() === $this) {
                $articlesUnit->setUnit(null);
            }
        }

        return $this;
    }
}
