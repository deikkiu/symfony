<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240917130701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE color ADD product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE color ADD CONSTRAINT FK_665648E94584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_665648E94584665A ON color (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE color DROP FOREIGN KEY FK_665648E94584665A');
        $this->addSql('DROP INDEX IDX_665648E94584665A ON color');
        $this->addSql('ALTER TABLE color DROP product_id');
    }
}
