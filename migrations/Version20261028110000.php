<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261028110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Configure whether payment methods are available for expenses';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('fuel_payment_method')->hasColumn('available_for_expenses')) {
            $this->addSql('ALTER TABLE fuel_payment_method ADD available_for_expenses TINYINT(1) DEFAULT 1 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('fuel_payment_method')->hasColumn('available_for_expenses')) {
            $this->addSql('ALTER TABLE fuel_payment_method DROP available_for_expenses');
        }
    }
}
