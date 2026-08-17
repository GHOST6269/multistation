<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260813074000 extends AbstractMigration
{
 public function getDescription():string{return 'Allow decimal article unit conversion factors';}
 public function up(Schema $schema):void{$this->addSql('ALTER TABLE articles_units CHANGE converstion_factor converstion_factor NUMERIC(15, 6) NOT NULL');}
 public function down(Schema $schema):void{$this->addSql('ALTER TABLE articles_units CHANGE converstion_factor converstion_factor NUMERIC(15, 0) NOT NULL');}
}
