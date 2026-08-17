<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;use Doctrine\Migrations\AbstractMigration;
final class Version20260813081500 extends AbstractMigration{public function getDescription():string{return 'Add movement reason and creation date';}public function up(Schema $schema):void{$this->addSql('ALTER TABLE mouvement_stock ADD reason VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');$this->addSql('UPDATE mouvement_stock SET created_at = NOW() WHERE created_at IS NULL');$this->addSql('ALTER TABLE mouvement_stock CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');}public function down(Schema $schema):void{$this->addSql('ALTER TABLE mouvement_stock DROP reason, DROP created_at');}}
