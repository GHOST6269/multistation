<?php

namespace App\Entity;

use App\Repository\StationsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StationsRepository::class)]
class Stations
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
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTime $creatAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gerant = null;

    /**
     * @var Collection<int, StationUsers>
     */
    #[ORM\OneToMany(targetEntity: StationUsers::class, mappedBy: 'station')]
    private Collection $stationUsers;

    /**
     * @var Collection<int, StationArticles>
     */
    #[ORM\OneToMany(targetEntity: StationArticles::class, mappedBy: 'station')]
    private Collection $stationArticles;

    public function __construct()
    {
        $this->stationUsers = new ArrayCollection();
        $this->stationArticles = new ArrayCollection();
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getGerant(): ?string
    {
        return $this->gerant;
    }

    public function setGerant(?string $gerant): static
    {
        $this->gerant = $gerant;

        return $this;
    }

    /**
     * @return Collection<int, StationUsers>
     */
    public function getStationUsers(): Collection
    {
        return $this->stationUsers;
    }

    public function addStationUser(StationUsers $stationUser): static
    {
        if (!$this->stationUsers->contains($stationUser)) {
            $this->stationUsers->add($stationUser);
            $stationUser->setStation($this);
        }

        return $this;
    }

    public function removeStationUser(StationUsers $stationUser): static
    {
        if ($this->stationUsers->removeElement($stationUser)) {
            // set the owning side to null (unless already changed)
            if ($stationUser->getStation() === $this) {
                $stationUser->setStation(null);
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
            $stationArticle->setStation($this);
        }

        return $this;
    }

    public function removeStationArticle(StationArticles $stationArticle): static
    {
        if ($this->stationArticles->removeElement($stationArticle)) {
            // set the owning side to null (unless already changed)
            if ($stationArticle->getStation() === $this) {
                $stationArticle->setStation(null);
            }
        }

        return $this;
    }
}
