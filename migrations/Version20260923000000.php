<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make the attendant optional on fuel readings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_shift_reading CHANGE attendant_id attendant_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE fuel_shift_reading SET attendant_id = (SELECT id FROM pump_attendant ORDER BY id LIMIT 1) WHERE attendant_id IS NULL');
        $this->addSql('ALTER TABLE fuel_shift_reading CHANGE attendant_id attendant_id INT NOT NULL');
    }
}
