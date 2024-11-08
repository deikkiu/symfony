<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241108060759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, status INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, count_imported_products INT DEFAULT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE import_message (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, import_product_id INT NOT NULL, INDEX IDX_A5AD9993870E5849 (import_product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, price_for_one INT NOT NULL, app_order_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_52EA1F09851F0D95 (app_order_id), INDEX IDX_52EA1F094584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE import_message ADD CONSTRAINT FK_A5AD9993870E5849 FOREIGN KEY (import_product_id) REFERENCES import (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09851F0D95 FOREIGN KEY (app_order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE import_product_message DROP FOREIGN KEY FK_45BE6295870E5849');
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE64584665A');
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE6851F0D95');
        $this->addSql('DROP TABLE import_product_message');
        $this->addSql('DROP TABLE order_product');
        $this->addSql('DROP TABLE import_product');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_product_message (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, import_product_id INT NOT NULL, INDEX IDX_45BE6295870E5849 (import_product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE order_product (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, price_for_one INT NOT NULL, app_order_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_2530ADE64584665A (product_id), INDEX IDX_2530ADE6851F0D95 (app_order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE import_product (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, status INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, count_imported_products INT DEFAULT NULL, slug VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE import_product_message ADD CONSTRAINT FK_45BE6295870E5849 FOREIGN KEY (import_product_id) REFERENCES import_product (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE64584665A FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE6851F0D95 FOREIGN KEY (app_order_id) REFERENCES `order` (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE import_message DROP FOREIGN KEY FK_A5AD9993870E5849');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09851F0D95');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('DROP TABLE import');
        $this->addSql('DROP TABLE import_message');
        $this->addSql('DROP TABLE order_item');
    }
}
