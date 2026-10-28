<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025110000 extends AbstractMigration
{
    public function getDescription(): string { return 'Link TAMANA+ cards to existing station customers'; }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('tamana_card');
        if (!array_filter($columns, static fn ($column): bool => strcasecmp($column->getName(), 'customer_id') === 0)) $this->addSql('ALTER TABLE tamana_card ADD customer_id INT DEFAULT NULL');
        $indexes = $schemaManager->listTableIndexes('tamana_card');
        if (!array_filter($indexes, static fn ($index): bool => strcasecmp($index->getName(), 'IDX_TAMANA_CARD_CUSTOMER') === 0)) $this->addSql('CREATE INDEX IDX_TAMANA_CARD_CUSTOMER ON tamana_card (customer_id)');
        $foreignKeys = $schemaManager->listTableForeignKeys('tamana_card');
        if (!array_filter($foreignKeys, static fn ($foreignKey): bool => strcasecmp($foreignKey->getName(), 'FK_TAMANA_CARD_CUSTOMER') === 0)) $this->addSql('ALTER TABLE tamana_card ADD CONSTRAINT FK_TAMANA_CARD_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tamana_card DROP FOREIGN KEY FK_TAMANA_CARD_CUSTOMER');
        $this->addSql('DROP INDEX IDX_TAMANA_CARD_CUSTOMER ON tamana_card');
        $this->addSql('ALTER TABLE tamana_card DROP customer_id');
    }
}
