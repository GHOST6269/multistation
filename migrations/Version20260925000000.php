<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track supplier deductions from selected fuel payment methods';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_payment_method ADD supplier_deduction TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE fuel_payment_method SET supplier_deduction = 1 WHERE code IN ('FANILO', 'TPE', 'VISA', 'FMS')");
        $this->addSql('ALTER TABLE supplier_payment ADD source_reading_id INT DEFAULT NULL, ADD source_payment_index INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SUPPLIER_PAYMENT_SOURCE ON supplier_payment (source_reading_id, source_payment_index)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_SUPPLIER_PAYMENT_SOURCE ON supplier_payment');
        $this->addSql('ALTER TABLE supplier_payment DROP source_reading_id, DROP source_payment_index');
        $this->addSql('ALTER TABLE fuel_payment_method DROP supplier_deduction');
    }
}
