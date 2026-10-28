<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025100000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add an independent TAMANA+ prepaid card ledger and station settlement register'; }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['tamana_card'])) $this->addSql("CREATE TABLE tamana_card (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, card_number VARCHAR(80) NOT NULL, holder_name VARCHAR(150) NOT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_tamana_card_station_number (station_id, card_number), INDEX IDX_TAMANA_CARD_STATION (station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        if (!$schemaManager->tablesExist(['tamana_transaction'])) $this->addSql("CREATE TABLE tamana_transaction (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, card_id INT NOT NULL, performed_by_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, amount NUMERIC(15, 2) NOT NULL, balance_after NUMERIC(15, 2) NOT NULL, transaction_date DATE NOT NULL, reference VARCHAR(100) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_tamana_tx_station_date (station_id, transaction_date), INDEX IDX_TAMANA_TX_CARD (card_id), INDEX IDX_TAMANA_TX_USER (performed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        if (!$schemaManager->tablesExist(['tamana_settlement'])) $this->addSql("CREATE TABLE tamana_settlement (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, performed_by_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, amount NUMERIC(15, 2) NOT NULL, settlement_date DATE NOT NULL, reference VARCHAR(100) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_tamana_settlement_station_date (station_id, settlement_date), INDEX IDX_TAMANA_SETTLEMENT_USER (performed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addForeignKeyIfMissing('tamana_card', 'FK_TAMANA_CARD_STATION', 'station_id', 'stations', 'CASCADE');
        $this->addForeignKeyIfMissing('tamana_transaction', 'FK_TAMANA_TX_STATION', 'station_id', 'stations', 'CASCADE');
        $this->addForeignKeyIfMissing('tamana_transaction', 'FK_TAMANA_TX_CARD', 'card_id', 'tamana_card', 'CASCADE');
        $this->addForeignKeyIfMissing('tamana_transaction', 'FK_TAMANA_TX_USER', 'performed_by_id', 'user', 'SET NULL');
        $this->addForeignKeyIfMissing('tamana_settlement', 'FK_TAMANA_SETTLEMENT_STATION', 'station_id', 'stations', 'CASCADE');
        $this->addForeignKeyIfMissing('tamana_settlement', 'FK_TAMANA_SETTLEMENT_USER', 'performed_by_id', 'user', 'SET NULL');
    }

    private function addForeignKeyIfMissing(string $table, string $name, string $column, string $referencedTable, string $onDelete): void
    {
        foreach ($this->connection->createSchemaManager()->listTableForeignKeys($table) as $foreignKey) {
            if (strcasecmp($foreignKey->getName(), $name) === 0) return;
        }
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id) ON DELETE %s', $table, $name, $column, $referencedTable, $onDelete));
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tamana_settlement');
        $this->addSql('DROP TABLE tamana_transaction');
        $this->addSql('DROP TABLE tamana_card');
    }
}
