<?php

namespace App\Entity;

use App\Repository\ArticlesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticlesRepository::class)]
class Articles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    private ?ArticleCategorie $categorie = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTime $creatAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updateAt = null;

    /**
     * @var Collection<int, ArticlesUnits>
     */
    #[ORM\OneToMany(targetEntity: ArticlesUnits::class, mappedBy: 'article')]
    private Collection $articlesUnits;

    /**
     * @var Collection<int, StationArticles>
     */
    #[ORM\OneToMany(targetEntity: StationArticles::class, mappedBy: 'article')]
    private Collection $stationArticles;

    public function __construct()
    {
        $this->articlesUnits = new ArrayCollection();
        $this->stationArticles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorie(): ?ArticleCategorie
    {
        return $this->categorie;
    }

    public function setCategorie(?ArticleCategorie $categorie): static
    {
        $this->categorie = $categorie;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getUpdateAt(): ?\DateTime
    {
        return $this->updateAt;
    }

    public function setUpdateAt(?\DateTime $updateAt): static
    {
        $this->updateAt = $updateAt;

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
            $articlesUnit->setArticle($this);
        }

        return $this;
    }

    public function removeArticlesUnit(ArticlesUnits $articlesUnit): static
    {
        if ($this->articlesUnits->removeElement($articlesUnit)) {
            // set the owning side to null (unless already changed)
            if ($articlesUnit->getArticle() === $this) {
                $articlesUnit->setArticle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StationArticles>
     */
    public function getStationArticles(): Collection
    {
        return $this->stationArticles;
    }

    public function addStationArticle(StationArticles $stationArticle): static
    {
        if (!$this->stationArticles->contains($stationArticle)) {
            $this->stationArticles->add($stationArticle);
            $stationArticle->setArticle($this);
        }

        return $this;
    }

    public function removeStationArticle(StationArticles $stationArticle): static
    {
        if ($this->stationArticles->removeElement($stationArticle)) {
            // set the owning side to null (unless already changed)
            if ($stationArticle->getArticle() === $this) {
                $stationArticle->setArticle(null);
            }
        }

        return $this;
    }
}
