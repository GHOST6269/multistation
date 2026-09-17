<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260917000000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add station customers and optional customer on fuel shift readings'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, code VARCHAR(30) DEFAULT NULL, name VARCHAR(150) NOT NULL, contact_person VARCHAR(100) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_81398E09F39D5D7 (station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09F39D5D7 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE fuel_shift_reading ADD customer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fuel_shift_reading ADD CONSTRAINT FK_88C5DC205939D6C FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('CREATE INDEX IDX_88C5DC205939D6C ON fuel_shift_reading (customer_id)');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_shift_reading DROP FOREIGN KEY FK_88C5DC205939D6C');
        $this->addSql('DROP INDEX IDX_88C5DC205939D6C ON fuel_shift_reading');
        $this->addSql('ALTER TABLE fuel_shift_reading DROP customer_id');
        $this->addSql('DROP TABLE customer');
    }
}
