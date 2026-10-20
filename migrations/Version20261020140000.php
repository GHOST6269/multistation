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
        $this->addSql("CREATE TABLE fuel_attendant_assignment (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, nozzle_id INT NOT NULL, attendant_id INT NOT NULL, assigned_at DATETIME DEFAULT NULL, removed_at DATETIME DEFAULT NULL, assigned_by_id INT DEFAULT NULL, removed_by_id INT DEFAULT NULL, INDEX idx_attendant_assignment_station (station_id, assigned_at), INDEX idx_attendant_assignment_nozzle_active (nozzle_id, removed_at), INDEX IDX_FUEL_ATTENDANT_ASSIGNMENT_ATTENDANT (attendant_id), INDEX IDX_FUEL_ATTENDANT_ASSIGNMENT_ASSIGNED_BY (assigned_by_id), INDEX IDX_FUEL_ATTENDANT_ASSIGNMENT_REMOVED_BY (removed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE fuel_attendant_assignment ADD CONSTRAINT FK_FUEL_ATTENDANT_ASSIGNMENT_STATION FOREIGN KEY (station_id) REFERENCES stations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fuel_attendant_assignment ADD CONSTRAINT FK_FUEL_ATTENDANT_ASSIGNMENT_NOZZLE FOREIGN KEY (nozzle_id) REFERENCES fuel_nozzle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fuel_attendant_assignment ADD CONSTRAINT FK_FUEL_ATTENDANT_ASSIGNMENT_ATTENDANT FOREIGN KEY (attendant_id) REFERENCES pump_attendant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fuel_attendant_assignment ADD CONSTRAINT FK_FUEL_ATTENDANT_ASSIGNMENT_ASSIGNED_BY FOREIGN KEY (assigned_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE fuel_attendant_assignment ADD CONSTRAINT FK_FUEL_ATTENDANT_ASSIGNMENT_REMOVED_BY FOREIGN KEY (removed_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE fuel_attendant_assignment');
    }
}
