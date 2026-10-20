<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261020101414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY `FK_81398E09F39D5D7`');
        $this->addSql('ALTER TABLE customer CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('DROP INDEX idx_81398e09f39d5d7 ON customer');
        $this->addSql('CREATE INDEX IDX_81398E0921BDB235 ON customer (station_id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT `FK_81398E09F39D5D7` FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY `FK_A74C013C21BDB235`');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY `FK_A74C013C7DAA0B`');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY `FK_A74C013C939D6C`');
        $this->addSql('ALTER TABLE customer_payment CHANGE previous_balance previous_balance NUMERIC(15, 2) NOT NULL, CHANGE remaining_balance remaining_balance NUMERIC(15, 2) NOT NULL');
        $this->addSql('DROP INDEX idx_a74c013c21bdb235 ON customer_payment');
        $this->addSql('CREATE INDEX IDX_71F520B321BDB235 ON customer_payment (station_id)');
        $this->addSql('DROP INDEX idx_a74c013c939d6c ON customer_payment');
        $this->addSql('CREATE INDEX IDX_71F520B39395C3F3 ON customer_payment (customer_id)');
        $this->addSql('DROP INDEX idx_a74c013c7daa0b ON customer_payment');
        $this->addSql('CREATE INDEX IDX_71F520B32E65C292 ON customer_payment (performed_by_id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT `FK_A74C013C21BDB235` FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT `FK_A74C013C7DAA0B` FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT `FK_A74C013C939D6C` FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY `FK_2B0B8F9F21BDB235`');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY `FK_2B0B8F9F7DAA0B`');
        $this->addSql('DROP INDEX idx_2b0b8f9f21bdb235 ON expense');
        $this->addSql('CREATE INDEX IDX_2D3A8DA621BDB235 ON expense (station_id)');
        $this->addSql('DROP INDEX idx_2b0b8f9f7daa0b ON expense');
        $this->addSql('CREATE INDEX IDX_2D3A8DA62E65C292 ON expense (performed_by_id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT `FK_2B0B8F9F21BDB235` FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT `FK_2B0B8F9F7DAA0B` FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE fuel_delivery DROP FOREIGN KEY `FK_4FD246257DAA0B`');
        $this->addSql('DROP INDEX idx_4fd246257daa0b ON fuel_delivery');
        $this->addSql('CREATE INDEX IDX_89BD6BF2E65C292 ON fuel_delivery (performed_by_id)');
        $this->addSql('ALTER TABLE fuel_delivery ADD CONSTRAINT `FK_4FD246257DAA0B` FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE fuel_payment_method CHANGE allowed_roles allowed_roles JSON NOT NULL');
        $this->addSql('ALTER TABLE fuel_shift_reading DROP FOREIGN KEY `FK_88C5DC207DAA0B`');
        $this->addSql('DROP INDEX IDX_88C5DC207DAA0B ON fuel_shift_reading');
        $this->addSql('ALTER TABLE fuel_shift_reading DROP FOREIGN KEY `FK_88C5DC205939D6C`');
        $this->addSql('ALTER TABLE fuel_shift_reading DROP performed_by_id');
        $this->addSql('DROP INDEX idx_88c5dc205939d6c ON fuel_shift_reading');
        $this->addSql('CREATE INDEX IDX_88C5DC209395C3F3 ON fuel_shift_reading (customer_id)');
        $this->addSql('ALTER TABLE fuel_shift_reading ADD CONSTRAINT `FK_88C5DC205939D6C` FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE fuel_type CHANGE unit_price unit_price NUMERIC(15, 2) NOT NULL');
        $this->addSql('DROP INDEX IDX_SUPPLIER_PAYMENT_SOURCE ON supplier_payment');
        $this->addSql('ALTER TABLE supplier_payment DROP FOREIGN KEY `FK_9C2B58217DAA0B`');
        $this->addSql('DROP INDEX idx_9c2b58217daa0b ON supplier_payment');
        $this->addSql('CREATE INDEX IDX_EC4DF0122E65C292 ON supplier_payment (performed_by_id)');
        $this->addSql('ALTER TABLE supplier_payment ADD CONSTRAINT `FK_9C2B58217DAA0B` FOREIGN KEY (performed_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E0921BDB235');
        $this->addSql('ALTER TABLE customer CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('DROP INDEX idx_81398e0921bdb235 ON customer');
        $this->addSql('CREATE INDEX IDX_81398E09F39D5D7 ON customer (station_id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E0921BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_71F520B321BDB235');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_71F520B39395C3F3');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_71F520B32E65C292');
        $this->addSql('ALTER TABLE customer_payment CHANGE previous_balance previous_balance NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL, CHANGE remaining_balance remaining_balance NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('DROP INDEX idx_71f520b39395c3f3 ON customer_payment');
        $this->addSql('CREATE INDEX IDX_A74C013C939D6C ON customer_payment (customer_id)');
        $this->addSql('DROP INDEX idx_71f520b32e65c292 ON customer_payment');
        $this->addSql('CREATE INDEX IDX_A74C013C7DAA0B ON customer_payment (performed_by_id)');
        $this->addSql('DROP INDEX idx_71f520b321bdb235 ON customer_payment');
        $this->addSql('CREATE INDEX IDX_A74C013C21BDB235 ON customer_payment (station_id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_71F520B321BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_71F520B39395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_71F520B32E65C292 FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA621BDB235');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA62E65C292');
        $this->addSql('DROP INDEX idx_2d3a8da621bdb235 ON expense');
        $this->addSql('CREATE INDEX IDX_2B0B8F9F21BDB235 ON expense (station_id)');
        $this->addSql('DROP INDEX idx_2d3a8da62e65c292 ON expense');
        $this->addSql('CREATE INDEX IDX_2B0B8F9F7DAA0B ON expense (performed_by_id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA621BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA62E65C292 FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE fuel_delivery DROP FOREIGN KEY FK_89BD6BF2E65C292');
        $this->addSql('DROP INDEX idx_89bd6bf2e65c292 ON fuel_delivery');
        $this->addSql('CREATE INDEX IDX_4FD246257DAA0B ON fuel_delivery (performed_by_id)');
        $this->addSql('ALTER TABLE fuel_delivery ADD CONSTRAINT FK_89BD6BF2E65C292 FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE fuel_payment_method CHANGE allowed_roles allowed_roles JSON DEFAULT \'["ROLE_GERANT"]\' NOT NULL');
        $this->addSql('ALTER TABLE fuel_shift_reading DROP FOREIGN KEY FK_88C5DC209395C3F3');
        $this->addSql('ALTER TABLE fuel_shift_reading ADD performed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fuel_shift_reading ADD CONSTRAINT `FK_88C5DC207DAA0B` FOREIGN KEY (performed_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_88C5DC207DAA0B ON fuel_shift_reading (performed_by_id)');
        $this->addSql('DROP INDEX idx_88c5dc209395c3f3 ON fuel_shift_reading');
        $this->addSql('CREATE INDEX IDX_88C5DC205939D6C ON fuel_shift_reading (customer_id)');
        $this->addSql('ALTER TABLE fuel_shift_reading ADD CONSTRAINT FK_88C5DC209395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE fuel_type CHANGE unit_price unit_price NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE supplier_payment DROP FOREIGN KEY FK_EC4DF0122E65C292');
        $this->addSql('CREATE INDEX IDX_SUPPLIER_PAYMENT_SOURCE ON supplier_payment (source_reading_id, source_payment_index)');
        $this->addSql('DROP INDEX idx_ec4df0122e65c292 ON supplier_payment');
        $this->addSql('CREATE INDEX IDX_9C2B58217DAA0B ON supplier_payment (performed_by_id)');
        $this->addSql('ALTER TABLE supplier_payment ADD CONSTRAINT FK_EC4DF0122E65C292 FOREIGN KEY (performed_by_id) REFERENCES user (id)');
    }
}
