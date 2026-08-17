<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805124017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article_categorie (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE articles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, creat_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, categorie_id INT DEFAULT NULL, INDEX IDX_BFDD3168BCF5E72D (categorie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE articles_units (id INT AUTO_INCREMENT NOT NULL, converstion_factor NUMERIC(15, 0) NOT NULL, is_base_unit TINYINT NOT NULL, barcode VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, article_id INT NOT NULL, unit_id INT NOT NULL, INDEX IDX_1525CBD7294869C (article_id), INDEX IDX_1525CBDF8BD700D (unit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE mouvement_stock (id INT AUTO_INCREMENT NOT NULL, entered_quantity NUMERIC(15, 6) NOT NULL, conversion_factor NUMERIC(15, 6) NOT NULL, base_quantity NUMERIC(15, 6) NOT NULL, previous_stock_base NUMERIC(15, 6) NOT NULL, new_stock_base NUMERIC(15, 6) NOT NULL, mouvement_type VARCHAR(255) NOT NULL, station_article_id INT NOT NULL, article_unit_id INT NOT NULL, INDEX IDX_61E2C8EBF873CB1E (station_article_id), INDEX IDX_61E2C8EB156F34BA (article_unit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE shop_sale_items (id INT AUTO_INCREMENT NOT NULL, article_name_snapshot VARCHAR(255) NOT NULL, unit_name_snapshot VARCHAR(255) NOT NULL, conversion_factor_snapshot NUMERIC(15, 6) NOT NULL, quantity NUMERIC(15, 6) NOT NULL, base_quantity NUMERIC(15, 6) NOT NULL, unit_price NUMERIC(15, 2) NOT NULL, unit_cost NUMERIC(15, 2) DEFAULT NULL, discount_amount NUMERIC(15, 2) DEFAULT NULL, line_total NUMERIC(15, 2) NOT NULL, station_article_id INT NOT NULL, station_article_unit_id INT NOT NULL, INDEX IDX_4BB15CE8F873CB1E (station_article_id), INDEX IDX_4BB15CE8DAF6E14C (station_article_unit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE station_article_units (id INT AUTO_INCREMENT NOT NULL, purchase_price NUMERIC(15, 2) DEFAULT NULL, sale_price NUMERIC(15, 2) NOT NULL, wholesale_price NUMERIC(15, 2) NOT NULL, minimum_sale_price NUMERIC(15, 2) NOT NULL, is_active TINYINT NOT NULL, creat_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, station_article_id INT NOT NULL, article_unit_id INT NOT NULL, INDEX IDX_70F8FAFF873CB1E (station_article_id), INDEX IDX_70F8FAF156F34BA (article_unit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE station_articles (id INT AUTO_INCREMENT NOT NULL, current_sock_base NUMERIC(15, 6) NOT NULL, minimum_stock_base NUMERIC(15, 6) NOT NULL, is_active TINYINT NOT NULL, station_id INT NOT NULL, article_id INT NOT NULL, INDEX IDX_3347EB3E21BDB235 (station_id), INDEX IDX_3347EB3E7294869C (article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE station_users (id INT AUTO_INCREMENT NOT NULL, is_active TINYINT NOT NULL, assigned_at DATETIME NOT NULL, station_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_4C05DADF21BDB235 (station_id), INDEX IDX_4C05DADFA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stations (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, creat_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, gerant VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE units (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, symbol VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, last_login DATETIME DEFAULT NULL, creat_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD3168BCF5E72D FOREIGN KEY (categorie_id) REFERENCES article_categorie (id)');
        $this->addSql('ALTER TABLE articles_units ADD CONSTRAINT FK_1525CBD7294869C FOREIGN KEY (article_id) REFERENCES articles (id)');
        $this->addSql('ALTER TABLE articles_units ADD CONSTRAINT FK_1525CBDF8BD700D FOREIGN KEY (unit_id) REFERENCES units (id)');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EBF873CB1E FOREIGN KEY (station_article_id) REFERENCES station_articles (id)');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EB156F34BA FOREIGN KEY (article_unit_id) REFERENCES articles_units (id)');
        $this->addSql('ALTER TABLE shop_sale_items ADD CONSTRAINT FK_4BB15CE8F873CB1E FOREIGN KEY (station_article_id) REFERENCES station_articles (id)');
        $this->addSql('ALTER TABLE shop_sale_items ADD CONSTRAINT FK_4BB15CE8DAF6E14C FOREIGN KEY (station_article_unit_id) REFERENCES station_article_units (id)');
        $this->addSql('ALTER TABLE station_article_units ADD CONSTRAINT FK_70F8FAFF873CB1E FOREIGN KEY (station_article_id) REFERENCES station_articles (id)');
        $this->addSql('ALTER TABLE station_article_units ADD CONSTRAINT FK_70F8FAF156F34BA FOREIGN KEY (article_unit_id) REFERENCES articles_units (id)');
        $this->addSql('ALTER TABLE station_articles ADD CONSTRAINT FK_3347EB3E21BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE station_articles ADD CONSTRAINT FK_3347EB3E7294869C FOREIGN KEY (article_id) REFERENCES articles (id)');
        $this->addSql('ALTER TABLE station_users ADD CONSTRAINT FK_4C05DADF21BDB235 FOREIGN KEY (station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE station_users ADD CONSTRAINT FK_4C05DADFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articles DROP FOREIGN KEY FK_BFDD3168BCF5E72D');
        $this->addSql('ALTER TABLE articles_units DROP FOREIGN KEY FK_1525CBD7294869C');
        $this->addSql('ALTER TABLE articles_units DROP FOREIGN KEY FK_1525CBDF8BD700D');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EBF873CB1E');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EB156F34BA');
        $this->addSql('ALTER TABLE shop_sale_items DROP FOREIGN KEY FK_4BB15CE8F873CB1E');
        $this->addSql('ALTER TABLE shop_sale_items DROP FOREIGN KEY FK_4BB15CE8DAF6E14C');
        $this->addSql('ALTER TABLE station_article_units DROP FOREIGN KEY FK_70F8FAFF873CB1E');
        $this->addSql('ALTER TABLE station_article_units DROP FOREIGN KEY FK_70F8FAF156F34BA');
        $this->addSql('ALTER TABLE station_articles DROP FOREIGN KEY FK_3347EB3E21BDB235');
        $this->addSql('ALTER TABLE station_articles DROP FOREIGN KEY FK_3347EB3E7294869C');
        $this->addSql('ALTER TABLE station_users DROP FOREIGN KEY FK_4C05DADF21BDB235');
        $this->addSql('ALTER TABLE station_users DROP FOREIGN KEY FK_4C05DADFA76ED395');
        $this->addSql('DROP TABLE article_categorie');
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE articles_units');
        $this->addSql('DROP TABLE mouvement_stock');
        $this->addSql('DROP TABLE shop_sale_items');
        $this->addSql('DROP TABLE station_article_units');
        $this->addSql('DROP TABLE station_articles');
        $this->addSql('DROP TABLE station_users');
        $this->addSql('DROP TABLE stations');
        $this->addSql('DROP TABLE units');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
