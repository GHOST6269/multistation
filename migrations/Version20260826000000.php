<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move fuel prices from nozzles to fuel types';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE fuel_type ADD unit_price NUMERIC(15, 2) DEFAULT '0' NOT NULL");
        $this->addSql("UPDATE fuel_type f JOIN (SELECT t.fuel_type_id, MAX(n.unit_price) AS unit_price FROM fuel_nozzle n JOIN fuel_tank t ON t.id = n.tank_id GROUP BY t.fuel_type_id) prices ON prices.fuel_type_id = f.id SET f.unit_price = prices.unit_price");
        $this->addSql('ALTER TABLE fuel_nozzle DROP COLUMN unit_price');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE fuel_nozzle ADD unit_price NUMERIC(15, 2) DEFAULT '0' NOT NULL");
        $this->addSql("UPDATE fuel_nozzle n JOIN fuel_tank t ON t.id = n.tank_id JOIN fuel_type f ON f.id = t.fuel_type_id SET n.unit_price = f.unit_price");
        $this->addSql('ALTER TABLE fuel_type DROP COLUMN unit_price');
    }
}