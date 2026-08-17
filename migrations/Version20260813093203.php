<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813093203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fuel_payment_method (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(40) NOT NULL, name VARCHAR(100) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, station_id INT NOT NULL, INDEX IDX_6829B7BC21BDB235 (station_id), UNIQUE INDEX uniq_payment_method_station_code (station_id, code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE fuel_payment_method ADD CONSTRAINT FK_6829B7BC21BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fuel_payment_method DROP FOREIGN KEY FK_6829B7BC21BDB235');
        $this->addSql('DROP TABLE fuel_payment_method');
    }
}
