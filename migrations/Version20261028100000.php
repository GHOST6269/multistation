<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261028100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow expenses to reference an optional supplier';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense ADD supplier_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_EXPENSE_SUPPLIER ON expense (supplier_id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_EXPENSE_SUPPLIER FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_EXPENSE_SUPPLIER');
        $this->addSql('DROP INDEX IDX_EXPENSE_SUPPLIER ON expense');
        $this->addSql('ALTER TABLE expense DROP supplier_id');
    }
}
