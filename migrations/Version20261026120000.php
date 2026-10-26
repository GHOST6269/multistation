<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Track supplier deductions received from customer account payments'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplier_payment ADD source_customer_payment_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_SUPPLIER_PAYMENT_CUSTOMER_SOURCE ON supplier_payment (source_customer_payment_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_SUPPLIER_PAYMENT_CUSTOMER_SOURCE ON supplier_payment');
        $this->addSql('ALTER TABLE supplier_payment DROP source_customer_payment_id');
    }
}
