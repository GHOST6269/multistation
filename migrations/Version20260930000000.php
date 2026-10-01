<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Configure credit payment methods and preserve their account behavior';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_payment_method ADD is_credit TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE fuel_payment_method SET is_credit = 1 WHERE code = 'CLIENT_VOUCHER'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fuel_payment_method DROP is_credit');
    }
}
