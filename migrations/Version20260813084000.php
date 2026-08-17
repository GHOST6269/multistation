<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;use Doctrine\Migrations\AbstractMigration;
final class Version20260813084000 extends AbstractMigration{public function getDescription():string{return 'Store detailed multi-unit physical counts';}public function up(Schema $schema):void{$this->addSql('ALTER TABLE mouvement_stock ADD details JSON DEFAULT NULL');}public function down(Schema $schema):void{$this->addSql('ALTER TABLE mouvement_stock DROP details');}}
