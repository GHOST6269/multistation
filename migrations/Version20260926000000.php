<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record supplier settlement receipts and invoice references for card sales';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_shift_reading ADD invoice_number VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier_payment ADD settlement_invoice_number VARCHAR(80) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_SUPPLIER_PAYMENT_SOURCE ON supplier_payment');
        $this->addSql('CREATE INDEX IDX_SUPPLIER_PAYMENT_SOURCE ON supplier_payment (source_reading_id, source_payment_index)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_SUPPLIER_PAYMENT_SOURCE ON supplier_payment');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SUPPLIER_PAYMENT_SOURCE ON supplier_payment (source_reading_id, source_payment_index)');
        $this->addSql('ALTER TABLE supplier_payment DROP settlement_invoice_number');
        $this->addSql('ALTER TABLE fuel_shift_reading DROP invoice_number');
    }
}
