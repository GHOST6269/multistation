<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;use Doctrine\Migrations\AbstractMigration;
final class Version20260813085000 extends AbstractMigration{public function getDescription():string{return 'Seed standard fuel types SP, GO and PL';}public function up(Schema $schema):void{$this->addSql("INSERT INTO fuel_type (code,name,is_active) VALUES ('SP','Super sans plomb',1),('GO','Gasoil',1),('PL','Pétrole lampant',1)");}public function down(Schema $schema):void{$this->addSql("DELETE FROM fuel_type WHERE code IN ('SP','GO','PL')");}}
