<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261020140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track pump attendant nozzle assignment and removal timestamps';
    }

    public function up(Schema $schema): void
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['fuel_attendant_assignment'])) {
            $this->addSql("CREATE TABLE fuel_attendant_assignment (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, nozzle_id INT NOT NULL, attendant_id INT NOT NULL, assigned_at DATETIME DEFAULT NULL, removed_at DATETIME DEFAULT NULL, assigned_by_id INT DEFAULT NULL, removed_by_id INT DEFAULT NULL, INDEX idx_attendant_assignment_station (station_id, assigned_at), INDEX idx_attendant_assignment_nozzle_active (nozzle_id, removed_at), INDEX IDX_FUEL_ATTENDANT_ASSIGNMENT_ATTENDANT (attendant_id), INDEX IDX_FUEL_ATTENDANT_ASSIGNMENT_ASSIGNED_BY (assigned_by_id), INDEX IDX_FUEL_ATTENDANT_ASSIGNMENT_REMOVED_BY (removed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        }
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_ASSIGNMENT_STATION', 'station_id', 'stations', 'CASCADE');
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_ASSIGNMENT_NOZZLE', 'nozzle_id', 'fuel_nozzle', 'CASCADE');
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_ASSIGNMENT_ATTENDANT', 'attendant_id', 'pump_attendant', 'CASCADE');
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_ASSIGNMENT_ASSIGNED_BY', 'assigned_by_id', 'user', 'SET NULL');
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_ASSIGNMENT_REMOVED_BY', 'removed_by_id', 'user', 'SET NULL');
    }

    private function addForeignKeyIfMissing(string $name, string $column, string $referencedTable, string $onDelete): void
    {
        foreach ($this->connection->createSchemaManager()->listTableForeignKeys('fuel_attendant_assignment') as $foreignKey) {
            if (strcasecmp($foreignKey->getName(), $name) === 0) return;
        }
        $this->addSql(sprintf('ALTER TABLE fuel_attendant_assignment ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id) ON DELETE %s', $name, $column, $referencedTable, $onDelete));
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE fuel_attendant_assignment');
    }
}
