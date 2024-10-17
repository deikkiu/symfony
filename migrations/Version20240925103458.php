<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240925103458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product CHANGE amount amount INT DEFAULT NULL, CHANGE product_attr_id product_attr_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_attr CHANGE weight weight INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_attr CHANGE weight weight INT NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE amount amount INT NOT NULL, CHANGE product_attr_id product_attr_id INT NOT NULL');
    }
}
