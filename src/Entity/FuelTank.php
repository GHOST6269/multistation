<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class FuelTank {
 #[ORM\Id,ORM\GeneratedValue,ORM\Column] private ?int $id=null;
 #[ORM\ManyToOne,ORM\JoinColumn(nullable:false)] private ?Stations $station=null;
 #[ORM\ManyToOne,ORM\JoinColumn(nullable:false)] private ?FuelType $fuelType=null;
 #[ORM\Column(length:50)] private string $code='';
 #[ORM\Column(length:100)] private string $name='';
 #[ORM\Column(type:Types::DECIMAL,precision:15,scale:3)] private string $capacity='0';
 #[ORM\Column(type:Types::DECIMAL,precision:15,scale:3)] private string $currentStock='0';
 #[ORM\Column(type:Types::DECIMAL,precision:15,scale:3)] private string $minimumStock='0';
 #[ORM\Column(options:['default'=>true])] private bool $isActive=true;
 public function getId():?int{return $this->id;} public function getStation():?Stations{return $this->station;} public function setStation(Stations $v):static{$this->station=$v;return $this;} public function getFuelType():?FuelType{return $this->fuelType;} public function setFuelType(FuelType $v):static{$this->fuelType=$v;return $this;} public function getCode():string{return $this->code;} public function setCode(string $v):static{$this->code=$v;return $this;} public function getName():string{return $this->name;} public function setName(string $v):static{$this->name=$v;return $this;} public function getCapacity():string{return $this->capacity;} public function setCapacity(string $v):static{$this->capacity=$v;return $this;} public function getCurrentStock():string{return $this->currentStock;} public function setCurrentStock(string $v):static{$this->currentStock=$v;return $this;} public function getMinimumStock():string{return $this->minimumStock;} public function setMinimumStock(string $v):static{$this->minimumStock=$v;return $this;} public function isActive():bool{return $this->isActive;} public function setIsActive(bool $v):static{$this->isActive=$v;return $this;}
}
