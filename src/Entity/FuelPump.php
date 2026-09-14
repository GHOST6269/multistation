<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class FuelPump {
 #[ORM\Id,ORM\GeneratedValue,ORM\Column] private ?int $id=null;
 #[ORM\ManyToOne,ORM\JoinColumn(nullable:false)] private ?Stations $station=null;
 #[ORM\OneToMany(mappedBy:'pump', targetEntity:FuelNozzle::class)] private Collection $nozzles;
 #[ORM\Column(length:30)] private string $code='';
 #[ORM\Column(length:100)] private string $name='';
 #[ORM\Column(options:['default'=>true])] private bool $isActive=true;

 public function __construct() { $this->nozzles = new ArrayCollection(); }
 public function getId():?int{return $this->id;}public function getStation():?Stations{return $this->station;}public function setStation(Stations $v):static{$this->station=$v;return $this;}
 /** @return Collection<int, FuelNozzle> */ public function getNozzles():Collection{return $this->nozzles;}
 public function getCode():string{return $this->code;}public function setCode(string $v):static{$this->code=$v;return $this;}public function getName():string{return $this->name;}public function setName(string $v):static{$this->name=$v;return $this;}public function isActive():bool{return $this->isActive;}public function setIsActive(bool $v):static{$this->isActive=$v;return $this;}
}
