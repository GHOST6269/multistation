<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;use Doctrine\Migrations\AbstractMigration;
final class Version20260813083000 extends AbstractMigration{public function getDescription():string{return 'Add stocktake reference to stock movements';}public function up(Schema $schema):void{$this->addSql('ALTER TABLE mouvement_stock ADD reference VARCHAR(50) DEFAULT NULL');$this->addSql('CREATE INDEX IDX_MOVEMENT_REFERENCE ON mouvement_stock (reference)');}public function down(Schema $schema):void{$this->addSql('DROP INDEX IDX_MOVEMENT_REFERENCE ON mouvement_stock');$this->addSql('ALTER TABLE mouvement_stock DROP reference');}}
