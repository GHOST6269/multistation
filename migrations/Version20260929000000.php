<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment due dates to customer fuel readings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_shift_reading ADD due_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_shift_reading DROP due_date');
    }
}
