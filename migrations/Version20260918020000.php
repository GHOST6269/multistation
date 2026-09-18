<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add persisted balance snapshots to customer payments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_payment ADD previous_balance NUMERIC(15,2) NOT NULL DEFAULT 0, ADD remaining_balance NUMERIC(15,2) NOT NULL DEFAULT 0');
        $this->addSql('UPDATE customer_payment SET previous_balance = 0, remaining_balance = 0 WHERE previous_balance IS NULL OR remaining_balance IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_payment DROP previous_balance');
        $this->addSql('ALTER TABLE customer_payment DROP remaining_balance');
    }
}
