<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026140000 extends AbstractMigration
{
    public function getDescription(): string { return 'Classify payment methods for cash, bank, credit and supplier journals'; }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE fuel_payment_method ADD journal_type VARCHAR(20) DEFAULT 'BANK' NOT NULL");
        $this->addSql("UPDATE fuel_payment_method SET journal_type = CASE WHEN is_credit = 1 THEN 'CREDIT' WHEN supplier_deduction = 1 THEN 'SUPPLIER' WHEN code IN ('CASH', 'ESPECES', 'ESPECE') THEN 'CASH' ELSE 'BANK' END");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_payment_method DROP journal_type');
    }
}
