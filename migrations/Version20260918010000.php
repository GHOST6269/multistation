<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer visibility permission to user accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD can_view_customers TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP can_view_customers');
    }
}
