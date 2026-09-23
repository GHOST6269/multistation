<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Associate a fuel nozzle with its current pump attendant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_nozzle ADD attendant_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_743D9BE4DE0C235 ON fuel_nozzle (attendant_id)');
        $this->addSql('ALTER TABLE fuel_nozzle ADD CONSTRAINT FK_743D9BE4DE0C235 FOREIGN KEY (attendant_id) REFERENCES pump_attendant (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_nozzle DROP FOREIGN KEY FK_743D9BE4DE0C235');
        $this->addSql('DROP INDEX IDX_743D9BE4DE0C235 ON fuel_nozzle');
        $this->addSql('ALTER TABLE fuel_nozzle DROP attendant_id');
    }
}
