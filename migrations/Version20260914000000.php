<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure fuel payment methods store the allowed roles used by the role-based access model';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('fuel_payment_method');

        if (!$table->hasColumn('allowed_roles')) {
            $table->addColumn('allowed_roles', 'json', ['notnull' => true, 'default' => '[]']);
        }

        $this->addSql("UPDATE fuel_payment_method SET allowed_roles = CASE WHEN code = 'CASH' THEN '[\"ROLE_GERANT\", \"ROLE_ASSISTANT\"]' ELSE '[\"ROLE_GERANT\"]' END WHERE allowed_roles IS NULL OR allowed_roles = ''");

        if ($table->hasColumn('allowed_user_ids')) {
            $this->addSql('ALTER TABLE fuel_payment_method DROP allowed_user_ids');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('fuel_payment_method');

        if (!$table->hasColumn('allowed_roles')) {
            $table->addColumn('allowed_roles', 'json', ['notnull' => true, 'default' => '[]']);
        }

        if ($table->hasColumn('allowed_user_ids')) {
            $this->addSql('ALTER TABLE fuel_payment_method DROP allowed_user_ids');
        }
    }
}
