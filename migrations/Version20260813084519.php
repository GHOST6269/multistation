<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813084519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fuel_delivery (id INT AUTO_INCREMENT NOT NULL, supplier VARCHAR(120) NOT NULL, invoice_number VARCHAR(60) DEFAULT NULL, delivery_date DATE NOT NULL, quantity NUMERIC(15, 3) NOT NULL, unit_cost NUMERIC(15, 2) NOT NULL, total_amount NUMERIC(15, 2) NOT NULL, created_at DATETIME NOT NULL, station_id INT NOT NULL, tank_id INT NOT NULL, INDEX IDX_89BD6BF21BDB235 (station_id), INDEX IDX_89BD6BF15C652B5 (tank_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE fuel_nozzle (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, current_index NUMERIC(15, 3) NOT NULL, unit_price NUMERIC(15, 2) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, pump_id INT NOT NULL, tank_id INT NOT NULL, INDEX IDX_743D9BEB9769C65 (pump_id), INDEX IDX_743D9BE15C652B5 (tank_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE fuel_pump (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, name VARCHAR(100) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, station_id INT NOT NULL, INDEX IDX_8868E9FC21BDB235 (station_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE fuel_shift_reading (id INT AUTO_INCREMENT NOT NULL, work_date DATE NOT NULL, start_index NUMERIC(15, 3) NOT NULL, end_index NUMERIC(15, 3) NOT NULL, return_to_tank NUMERIC(15, 3) NOT NULL, quantity_sold NUMERIC(15, 3) NOT NULL, unit_price NUMERIC(15, 2) NOT NULL, total_amount NUMERIC(15, 2) NOT NULL, payments JSON NOT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, station_id INT NOT NULL, nozzle_id INT NOT NULL, attendant_id INT NOT NULL, INDEX IDX_88C5DC2021BDB235 (station_id), INDEX IDX_88C5DC2050F8DC12 (nozzle_id), INDEX IDX_88C5DC204DE0C235 (attendant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE fuel_tank (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(100) NOT NULL, capacity NUMERIC(15, 3) NOT NULL, current_stock NUMERIC(15, 3) NOT NULL, minimum_stock NUMERIC(15, 3) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, station_id INT NOT NULL, fuel_type_id INT NOT NULL, INDEX IDX_BD6DEF2821BDB235 (station_id), INDEX IDX_BD6DEF286A70FE35 (fuel_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE fuel_type (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, name VARCHAR(100) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_FUEL_CODE (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE pump_attendant (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) DEFAULT NULL, full_name VARCHAR(120) NOT NULL, contact VARCHAR(50) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, station_id INT NOT NULL, INDEX IDX_C8967CFA21BDB235 (station_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE fuel_delivery ADD CONSTRAINT FK_89BD6BF21BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE fuel_delivery ADD CONSTRAINT FK_89BD6BF15C652B5 FOREIGN KEY (tank_id) REFERENCES fuel_tank (id)');
        $this->addSql('ALTER TABLE fuel_nozzle ADD CONSTRAINT FK_743D9BEB9769C65 FOREIGN KEY (pump_id) REFERENCES fuel_pump (id)');
        $this->addSql('ALTER TABLE fuel_nozzle ADD CONSTRAINT FK_743D9BE15C652B5 FOREIGN KEY (tank_id) REFERENCES fuel_tank (id)');
        $this->addSql('ALTER TABLE fuel_pump ADD CONSTRAINT FK_8868E9FC21BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE fuel_shift_reading ADD CONSTRAINT FK_88C5DC2021BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE fuel_shift_reading ADD CONSTRAINT FK_88C5DC2050F8DC12 FOREIGN KEY (nozzle_id) REFERENCES fuel_nozzle (id)');
        $this->addSql('ALTER TABLE fuel_shift_reading ADD CONSTRAINT FK_88C5DC204DE0C235 FOREIGN KEY (attendant_id) REFERENCES pump_attendant (id)');
        $this->addSql('ALTER TABLE fuel_tank ADD CONSTRAINT FK_BD6DEF2821BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE fuel_tank ADD CONSTRAINT FK_BD6DEF286A70FE35 FOREIGN KEY (fuel_type_id) REFERENCES fuel_type (id)');
        $this->addSql('ALTER TABLE pump_attendant ADD CONSTRAINT FK_C8967CFA21BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fuel_delivery DROP FOREIGN KEY FK_89BD6BF21BDB235');
        $this->addSql('ALTER TABLE fuel_delivery DROP FOREIGN KEY FK_89BD6BF15C652B5');
        $this->addSql('ALTER TABLE fuel_nozzle DROP FOREIGN KEY FK_743D9BEB9769C65');
        $this->addSql('ALTER TABLE fuel_nozzle DROP FOREIGN KEY FK_743D9BE15C652B5');
        $this->addSql('ALTER TABLE fuel_pump DROP FOREIGN KEY FK_8868E9FC21BDB235');
        $this->addSql('ALTER TABLE fuel_shift_reading DROP FOREIGN KEY FK_88C5DC2021BDB235');
        $this->addSql('ALTER TABLE fuel_shift_reading DROP FOREIGN KEY FK_88C5DC2050F8DC12');
        $this->addSql('ALTER TABLE fuel_shift_reading DROP FOREIGN KEY FK_88C5DC204DE0C235');
        $this->addSql('ALTER TABLE fuel_tank DROP FOREIGN KEY FK_BD6DEF2821BDB235');
        $this->addSql('ALTER TABLE fuel_tank DROP FOREIGN KEY FK_BD6DEF286A70FE35');
        $this->addSql('ALTER TABLE pump_attendant DROP FOREIGN KEY FK_C8967CFA21BDB235');
        $this->addSql('DROP TABLE fuel_delivery');
        $this->addSql('DROP TABLE fuel_nozzle');
        $this->addSql('DROP TABLE fuel_pump');
        $this->addSql('DROP TABLE fuel_shift_reading');
        $this->addSql('DROP TABLE fuel_tank');
        $this->addSql('DROP TABLE fuel_type');
        $this->addSql('DROP TABLE pump_attendant');
    }
}
