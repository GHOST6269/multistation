<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260913000000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add per-role access controls to fuel payment methods'; }
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE fuel_payment_method ADD allowed_roles JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("UPDATE fuel_payment_method SET allowed_roles = CASE WHEN code = 'CASH' THEN '[\\\"ROLE_GERANT\\\", \\\"ROLE_ASSISTANT\\\"]' ELSE '[\\\"ROLE_GERANT\\\"]' END");
    }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE fuel_payment_method DROP allowed_roles'); }
}
