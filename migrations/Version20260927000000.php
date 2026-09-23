<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Generate invoice numbers for fuel readings and sales';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE fuel_shift_reading SET invoice_number = CONCAT('F-', YEAR(work_date), '-', id) WHERE invoice_number IS NULL OR invoice_number = ''");
    }

    public function down(Schema $schema): void
    {
        // Existing invoice numbers are retained when rolling back this data migration.
    }
}
