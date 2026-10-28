<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20261020130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store attendant settlements independently from fuel readings';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['fuel_attendant_payment'])) {
            $this->addSql("CREATE TABLE fuel_attendant_payment (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, attendant_id INT DEFAULT NULL, payment_method_id INT DEFAULT NULL, customer_id INT DEFAULT NULL, entered_by_id INT DEFAULT NULL, payment_method_name VARCHAR(100) NOT NULL, reference VARCHAR(80) DEFAULT NULL, payment_date DATE NOT NULL, reading_date DATE NOT NULL, responsible VARCHAR(120) NOT NULL, amount NUMERIC(15, 2) NOT NULL, created_at DATETIME NOT NULL, INDEX idx_attendant_payment_day (station_id, attendant_id, reading_date), INDEX idx_attendant_payment_date (station_id, payment_date), INDEX IDX_FUEL_ATTENDANT_PAYMENT_ATTENDANT (attendant_id), INDEX IDX_FUEL_ATTENDANT_PAYMENT_METHOD (payment_method_id), INDEX IDX_FUEL_ATTENDANT_PAYMENT_CUSTOMER (customer_id), INDEX IDX_FUEL_ATTENDANT_PAYMENT_USER (entered_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        }
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_PAYMENT_STATION', 'station_id', 'stations', 'CASCADE');
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_PAYMENT_ATTENDANT', 'attendant_id', 'pump_attendant', 'SET NULL');
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_PAYMENT_METHOD', 'payment_method_id', 'fuel_payment_method', 'SET NULL');
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_PAYMENT_CUSTOMER', 'customer_id', 'customer', 'SET NULL');
        $this->addForeignKeyIfMissing('FK_FUEL_ATTENDANT_PAYMENT_USER', 'entered_by_id', 'user', 'SET NULL');

        $connection = $this->connection;
        $readings = $connection->executeQuery('SELECT r.id, r.station_id, r.attendant_id, r.work_date, r.payments, r.created_at AS reading_created_at, a.full_name AS attendant_name FROM fuel_shift_reading r LEFT JOIN pump_attendant a ON a.id = r.attendant_id')->fetchAllAssociative();
        foreach ($readings as $reading) {
            $payments = json_decode((string) $reading['payments'], true);
            if (!is_array($payments)) continue;
            $remaining = [];
            foreach ($payments as $payment) {
                if (!is_array($payment) || empty($payment['attendantSettlement'])) {
                    $remaining[] = $payment;
                    continue;
                }
                if (!$reading['attendant_id']) {
                    $remaining[] = $payment;
                    continue;
                }
                $enteredById = null;
                $enteredByName = trim((string) ($payment['performedBy'] ?? ''));
                if ($enteredByName !== '') {
                    $matchingUsers = $connection->fetchFirstColumn(
                        "SELECT id FROM user WHERE TRIM(CONCAT(first_name, ' ', COALESCE(last_name, ''))) = ?",
                        [$enteredByName],
                    );
                    if (count($matchingUsers) === 1) $enteredById = (int) $matchingUsers[0];
                }
                $connection->insert('fuel_attendant_payment', [
                    'station_id' => $reading['station_id'],
                    'attendant_id' => $reading['attendant_id'],
                    'payment_method_id' => $payment['methodId'] ?? null,
                    'customer_id' => $payment['customerId'] ?? null,
                    'entered_by_id' => $enteredById,
                    'payment_method_name' => (string) ($payment['label'] ?? $payment['type'] ?? 'Mode inconnu'),
                    'reference' => $payment['reference'] ?? null,
                    'payment_date' => $payment['date'] ?? $reading['work_date'],
                    'reading_date' => $reading['work_date'],
                    'responsible' => (string) ($payment['responsible'] ?? $reading['attendant_name'] ?? ''),
                    'amount' => (float) ($payment['amount'] ?? 0),
                    'created_at' => $reading['reading_created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
            if (count($remaining) !== count($payments)) {
                $connection->update('fuel_shift_reading', ['payments' => json_encode(array_values($remaining), JSON_THROW_ON_ERROR)], ['id' => $reading['id']]);
            }
        }
    }

    private function addForeignKeyIfMissing(string $name, string $column, string $referencedTable, string $onDelete): void
    {
        foreach ($this->connection->createSchemaManager()->listTableForeignKeys('fuel_attendant_payment') as $foreignKey) {
            if (strcasecmp($foreignKey->getName(), $name) === 0) return;
        }
        $this->addSql(sprintf('ALTER TABLE fuel_attendant_payment ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id) ON DELETE %s', $name, $column, $referencedTable, $onDelete));
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Settlement history is no longer linked to individual fuel readings and cannot be moved back safely.');
    }
}
