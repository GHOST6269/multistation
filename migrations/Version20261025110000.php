<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025110000 extends AbstractMigration
{
    public function getDescription(): string { return 'Link TAMANA+ cards to existing station customers'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tamana_card ADD customer_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_TAMANA_CARD_CUSTOMER ON tamana_card (customer_id)');
        $this->addSql('ALTER TABLE tamana_card ADD CONSTRAINT FK_TAMANA_CARD_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tamana_card DROP FOREIGN KEY FK_TAMANA_CARD_CUSTOMER');
        $this->addSql('DROP INDEX IDX_TAMANA_CARD_CUSTOMER ON tamana_card');
        $this->addSql('ALTER TABLE tamana_card DROP customer_id');
    }
}
