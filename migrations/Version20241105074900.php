<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241105074900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, status INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE import_product_message (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, import_product_id INT NOT NULL, INDEX IDX_45BE6295870E5849 (import_product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE import_product_message ADD CONSTRAINT FK_45BE6295870E5849 FOREIGN KEY (import_product_id) REFERENCES import_product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_product_message DROP FOREIGN KEY FK_45BE6295870E5849');
        $this->addSql('DROP TABLE import_product');
        $this->addSql('DROP TABLE import_product_message');
    }
}
