<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813091131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE supplier (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) DEFAULT NULL, name VARCHAR(150) NOT NULL, contact_person VARCHAR(100) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, station_id INT NOT NULL, INDEX IDX_9B2A6C7E21BDB235 (station_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE supplier_invoice (id INT AUTO_INCREMENT NOT NULL, invoice_number VARCHAR(60) NOT NULL, invoice_date DATE NOT NULL, due_date DATE DEFAULT NULL, total_amount NUMERIC(15, 2) NOT NULL, invoice_type VARCHAR(30) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, supplier_id INT NOT NULL, station_id INT NOT NULL, delivery_id INT DEFAULT NULL, INDEX IDX_1100635B2ADD6D8C (supplier_id), INDEX IDX_1100635B21BDB235 (station_id), INDEX IDX_1100635B12136921 (delivery_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE supplier_payment (id INT AUTO_INCREMENT NOT NULL, payment_date DATE NOT NULL, amount NUMERIC(15, 2) NOT NULL, payment_method VARCHAR(30) NOT NULL, reference VARCHAR(80) DEFAULT NULL, status VARCHAR(30) NOT NULL, note VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, supplier_id INT NOT NULL, invoice_id INT DEFAULT NULL, station_id INT NOT NULL, INDEX IDX_EC4DF0122ADD6D8C (supplier_id), INDEX IDX_EC4DF0122989F1FD (invoice_id), INDEX IDX_EC4DF01221BDB235 (station_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE supplier ADD CONSTRAINT FK_9B2A6C7E21BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE supplier_invoice ADD CONSTRAINT FK_1100635B2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE supplier_invoice ADD CONSTRAINT FK_1100635B21BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE supplier_invoice ADD CONSTRAINT FK_1100635B12136921 FOREIGN KEY (delivery_id) REFERENCES fuel_delivery (id)');
        $this->addSql('ALTER TABLE supplier_payment ADD CONSTRAINT FK_EC4DF0122ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE supplier_payment ADD CONSTRAINT FK_EC4DF0122989F1FD FOREIGN KEY (invoice_id) REFERENCES supplier_invoice (id)');
        $this->addSql('ALTER TABLE supplier_payment ADD CONSTRAINT FK_EC4DF01221BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE supplier DROP FOREIGN KEY FK_9B2A6C7E21BDB235');
        $this->addSql('ALTER TABLE supplier_invoice DROP FOREIGN KEY FK_1100635B2ADD6D8C');
        $this->addSql('ALTER TABLE supplier_invoice DROP FOREIGN KEY FK_1100635B21BDB235');
        $this->addSql('ALTER TABLE supplier_invoice DROP FOREIGN KEY FK_1100635B12136921');
        $this->addSql('ALTER TABLE supplier_payment DROP FOREIGN KEY FK_EC4DF0122ADD6D8C');
        $this->addSql('ALTER TABLE supplier_payment DROP FOREIGN KEY FK_EC4DF0122989F1FD');
        $this->addSql('ALTER TABLE supplier_payment DROP FOREIGN KEY FK_EC4DF01221BDB235');
        $this->addSql('DROP TABLE supplier');
        $this->addSql('DROP TABLE supplier_invoice');
        $this->addSql('DROP TABLE supplier_payment');
    }
}
