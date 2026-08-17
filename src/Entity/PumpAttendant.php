<?php
namespace App\Entity;use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class PumpAttendant {
 #[ORM\Id,ORM\GeneratedValue,ORM\Column]private ?int $id=null;#[ORM\ManyToOne,ORM\JoinColumn(nullable:false)]private ?Stations $station=null;#[ORM\Column(length:30,nullable:true)]private ?string $code=null;#[ORM\Column(length:120)]private string $fullName='';#[ORM\Column(length:50,nullable:true)]private ?string $contact=null;#[ORM\Column(options:['default'=>true])]private bool $isActive=true;
 public function getId():?int{return $this->id;}public function getStation():?Stations{return $this->station;}public function setStation(Stations $v):static{$this->station=$v;return $this;}public function getCode():?string{return $this->code;}public function setCode(?string $v):static{$this->code=$v;return $this;}public function getFullName():string{return $this->fullName;}public function setFullName(string $v):static{$this->fullName=$v;return $this;}public function getContact():?string{return $this->contact;}public function setContact(?string $v):static{$this->contact=$v;return $this;}public function isActive():bool{return $this->isActive;}public function setIsActive(bool $v):static{$this->isActive=$v;return $this;}
}
