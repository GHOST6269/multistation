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
        $this->addSql("CREATE TABLE tamana_card (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, card_number VARCHAR(80) NOT NULL, holder_name VARCHAR(150) NOT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_tamana_card_station_number (station_id, card_number), INDEX IDX_TAMANA_CARD_STATION (station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE tamana_card ADD CONSTRAINT FK_TAMANA_CARD_STATION FOREIGN KEY (station_id) REFERENCES stations (id) ON DELETE CASCADE');
        $this->addSql("CREATE TABLE tamana_transaction (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, card_id INT NOT NULL, performed_by_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, amount NUMERIC(15, 2) NOT NULL, balance_after NUMERIC(15, 2) NOT NULL, transaction_date DATE NOT NULL, reference VARCHAR(100) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_tamana_tx_station_date (station_id, transaction_date), INDEX IDX_TAMANA_TX_CARD (card_id), INDEX IDX_TAMANA_TX_USER (performed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE tamana_transaction ADD CONSTRAINT FK_TAMANA_TX_STATION FOREIGN KEY (station_id) REFERENCES stations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tamana_transaction ADD CONSTRAINT FK_TAMANA_TX_CARD FOREIGN KEY (card_id) REFERENCES tamana_card (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tamana_transaction ADD CONSTRAINT FK_TAMANA_TX_USER FOREIGN KEY (performed_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql("CREATE TABLE tamana_settlement (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, performed_by_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, amount NUMERIC(15, 2) NOT NULL, settlement_date DATE NOT NULL, reference VARCHAR(100) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_tamana_settlement_station_date (station_id, settlement_date), INDEX IDX_TAMANA_SETTLEMENT_USER (performed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE tamana_settlement ADD CONSTRAINT FK_TAMANA_SETTLEMENT_STATION FOREIGN KEY (station_id) REFERENCES stations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tamana_settlement ADD CONSTRAINT FK_TAMANA_SETTLEMENT_USER FOREIGN KEY (performed_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tamana_settlement');
        $this->addSql('DROP TABLE tamana_transaction');
        $this->addSql('DROP TABLE tamana_card');
    }
}
